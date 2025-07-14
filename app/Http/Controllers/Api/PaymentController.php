<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Midtrans\Config;
use Midtrans\Notification;

/**
 * PaymentController handles Midtrans payment processing
 *
 * Features:
 * 1. Webhook notification handler for Midtrans callbacks
 * 2. Manual payment processing for testing
 * 3. Payment status checking
 *
 * Webhook URL: POST /api/payment/notification
 * Configure this URL in your Midtrans dashboard settings
 */

class PaymentController extends Controller
{
    public function __construct()
    {
        // Set Midtrans configuration
        Config::$serverKey = config('services.midtrans.server_key');
        Config::$clientKey = config('services.midtrans.client_key');
        Config::$isProduction = config('services.midtrans.is_production');
        Config::$isSanitized = config('services.midtrans.is_sanitized');
        Config::$is3ds = config('services.midtrans.is_3ds');
    }

    /**
     * Handle Midtrans notification webhook
     */
    public function handleNotification(Request $request)
    {
        try {
            // Create notification instance
            $notification = new Notification();

            $orderId = $notification->order_id;
            $transactionStatus = $notification->transaction_status;
            $fraudStatus = $notification->fraud_status;
            $paymentType = $notification->payment_type;
            $transactionId = $notification->transaction_id;
            $grossAmount = $notification->gross_amount;

            Log::info('Midtrans notification received', [
                'order_id' => $orderId,
                'transaction_status' => $transactionStatus,
                'payment_type' => $paymentType,
                'transaction_id' => $transactionId
            ]);

            // Find the order
            $order = Order::find($orderId);
            if (!$order) {
                return response()->json(['message' => 'Order not found'], 404);
            }

            // Create or update payment record
            $payment = Payment::updateOrCreate(
                ['order_id' => $orderId],
                [
                    'transaction_id' => $transactionId,
                    'payment_type' => $paymentType,
                    'gross_amount' => $grossAmount,
                    'remaining_amount' => $order->amount - $grossAmount, // Calculate remaining amount
                    'transaction_status' => $transactionStatus,
                    'fraud_status' => $fraudStatus,
                    'payment_data' => json_encode($notification->getResponse()),
                    'amount' => $order->amount, // Total order amount
                    'payment_method' => $paymentType,
                ]
            );

            // Update order status based on transaction status
            $orderStatus = $this->mapTransactionStatusToOrderStatus($transactionStatus, $fraudStatus);

            // For successful advance payment, mark as advance_paid instead of completed
            if ($orderStatus === 'paid') {
                $orderStatus = 'advance_paid'; // Only 50% is paid online
                $payment->update(['status' => Payment::STATUS_COMPLETED]);
            }

            $order->update(['status' => $orderStatus]);

            // Handle table status updates based on payment success
            if ($orderStatus === 'advance_paid') {
                $this->handleAdvancePaymentSuccess($order);
            } elseif (in_array($orderStatus, ['cancelled', 'failed'])) {
                $this->handleFailedPayment($order);
            }
            // For 'pending' status, we don't need to do anything special -
            // stock is already decremented and will be restored if payment eventually fails

            return response()->json(['message' => 'Notification processed successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Midtrans notification processing failed', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json(['message' => 'Notification processing failed'], 500);
        }
    }

    /**
     * Manual payment handling (for testing or admin purposes)
     */
    public function handlePayment(Request $request)
    {
        // Validate and process the payment
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'payment_type' => 'required|string',
            'gross_amount' => 'required|numeric|min:0',
            'status_code'  => 'required',
            'transaction_status' => 'required|string',
        ]);

        // Process the payment
        $order = Order::findOrFail($request->order_id);

        // Update order status based on payment
        $order->update([
            'status' => $request->transaction_status,
        ]);

        // Update table status if needed
        // Table reservation logic
        $table = $order->table;
        $table->update([
            'status' => 'reserved',
        ]);

        // Additional logic after payment processing
        return response()->json([
            'message' => 'Payment processed successfully',
            'order' => $order,
        ], 200);
    }

    /**
     * Map Midtrans transaction status to order status
     */
    private function mapTransactionStatusToOrderStatus($transactionStatus, $fraudStatus = null)
    {
        switch ($transactionStatus) {
            case 'capture':
                return ($fraudStatus === 'challenge') ? 'pending' : 'paid';
            case 'settlement':
                return 'paid';
            case 'pending':
                return 'pending';
            case 'deny':
            case 'cancel':
            case 'expire':
                return 'cancelled';
            case 'failure':
                return 'failed';
            default:
                return 'pending';
        }
    }

    /**
     * Handle successful advance payment (50%)
     */
    private function handleAdvancePaymentSuccess(Order $order)
    {
        DB::transaction(function () use ($order) {
            // Update table status to reserved (advance payment confirms reservation)
            if ($order->table) {
                $order->table->update(['status' => 'reserved']);
            }

            // Stock remains decremented (already handled at order creation)
            // Advance payment confirms the reservation and stock allocation
            foreach ($order->orderItems as $orderItem) {
                Log::info('Advance payment confirmed for menu item', [
                    'menu_id' => $orderItem->menu_id,
                    'menu_name' => $orderItem->menu->name,
                    'quantity' => $orderItem->quantity,
                    'current_stock' => $orderItem->menu->stock
                ]);
            }
        });

        Log::info('Advance payment (50%) successful for order', [
            'order_id' => $order->id,
            'advance_amount' => $order->advance_amount,
            'remaining_amount' => $order->remaining_amount
        ]);
    }

    /**
     * Handle successful payment (kept for compatibility)
     */
    private function handleSuccessfulPayment(Order $order)
    {
        DB::transaction(function () use ($order) {
            // Update table status to reserved
            if ($order->table) {
                $order->table->update(['status' => 'reserved']);
            }

            // Stock was already decremented when order was created
            // So we don't need to decrement again on successful payment
            // Just log the confirmation
            foreach ($order->orderItems as $orderItem) {
                Log::info('Stock confirmed sold for menu item', [
                    'menu_id' => $orderItem->menu_id,
                    'menu_name' => $orderItem->menu->name,
                    'quantity' => $orderItem->quantity,
                    'current_stock' => $orderItem->menu->stock
                ]);
            }
        });

        // You can add more logic here like:
        // - Send confirmation email
        // - Send notification to restaurant
        // - Generate receipt

        Log::info('Payment successful for order', ['order_id' => $order->id]);
    }

    /**
     * Handle failed payment
     */
    private function handleFailedPayment(Order $order)
    {
        DB::transaction(function () use ($order) {
            // Keep table status as available
            if ($order->table) {
                $order->table->update(['status' => 'available']);
            }

            // Increment stock back for each order item (restore inventory)
            foreach ($order->orderItems as $orderItem) {
                $orderItem->menu->incrementStock($orderItem->quantity);

                Log::info('Stock restored for menu item', [
                    'menu_id' => $orderItem->menu_id,
                    'menu_name' => $orderItem->menu->name,
                    'quantity' => $orderItem->quantity,
                    'restored_stock' => $orderItem->menu->fresh()->stock
                ]);
            }
        });

        // You can add more logic here like:
        // - Send failure notification
        // - Cancel reservations

        Log::info('Payment failed for order', ['order_id' => $order->id]);
    }

    /**
     * Get payment status for an order
     */
    public function getPaymentStatus(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id'
        ]);

        $order = Order::with('payment')->find($request->order_id);

        return response()->json([
            'order_id' => $order->id,
            'order_status' => $order->status,
            'payment' => $order->payment ?? null
        ], 200);
    }

    /**
     * Cancel order and restore stock (for timeout or manual cancellation)
     */
    public function cancelOrder(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id'
        ]);

        $order = Order::with(['orderItems.menu', 'table'])->find($request->order_id);

        // Only allow cancellation of pending orders
        if ($order->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending orders can be cancelled',
                'current_status' => $order->status
            ], 400);
        }

        DB::transaction(function () use ($order) {
            // Restore stock for each order item
            foreach ($order->orderItems as $orderItem) {
                $orderItem->menu->incrementStock($orderItem->quantity);

                Log::info('Stock restored due to order cancellation', [
                    'order_id' => $order->id,
                    'menu_id' => $orderItem->menu_id,
                    'menu_name' => $orderItem->menu->name,
                    'quantity' => $orderItem->quantity,
                    'restored_stock' => $orderItem->menu->fresh()->stock
                ]);
            }

            // Update order status
            $order->update(['status' => 'cancelled']);

            // Free up the table
            if ($order->table) {
                $order->table->update(['status' => 'available']);
            }
        });

        return response()->json([
            'message' => 'Order cancelled successfully',
            'order' => $order
        ], 200);
    }

    /**
     * Complete remaining payment at restaurant (manual)
     */
    public function completeRemainingPayment(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'payment_method' => 'required|string', // cash, card, etc.
            'staff_id' => 'nullable|exists:users,id', // Staff who processed payment
        ]);

        $order = Order::with(['payment', 'orderItems.menu', 'table'])->find($request->order_id);

        // Only allow completion for advance_paid orders
        if ($order->status !== 'advance_paid') {
            return response()->json([
                'message' => 'Order must have advance payment completed first',
                'current_status' => $order->status,
                'required_status' => 'advance_paid'
            ], 400);
        }

        DB::transaction(function () use ($order, $request) {
            // Update payment record
            if ($order->payment) {
                $order->payment->update([
                    'status' => Payment::STATUS_COMPLETED,
                    'payment_data' => array_merge(
                        $order->payment->payment_data ?? [],
                        [
                            'remaining_payment' => [
                                'amount' => $order->remaining_amount,
                                'method' => $request->payment_method,
                                'staff_id' => $request->staff_id,
                                'completed_at' => now(),
                            ]
                        ]
                    )
                ]);
            }

            // Update order status to completed
            $order->update(['status' => 'completed']);

            // Update table status (can be available or in_use depending on restaurant policy)
            if ($order->table) {
                $order->table->update(['status' => 'in_use']); // Customer is dining
            }
        });

        Log::info('Remaining payment completed at restaurant', [
            'order_id' => $order->id,
            'remaining_amount' => $order->remaining_amount,
            'payment_method' => $request->payment_method,
            'staff_id' => $request->staff_id
        ]);

        return response()->json([
            'message' => 'Remaining payment completed successfully',
            'order' => $order->fresh(['payment']),
            'total_paid' => $order->amount,
            'advance_paid' => $order->advance_amount,
            'remaining_paid' => $order->remaining_amount
        ], 200);
    }

    /**
     * Confirm customer arrival (when customer arrives at restaurant)
     */
    public function confirmArrival(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'staff_id' => 'nullable|exists:users,id'
        ]);

        $order = Order::with(['table'])->find($request->order_id);

        if ($order->status !== 'advance_paid') {
            return response()->json([
                'message' => 'Order must have advance payment completed',
                'current_status' => $order->status
            ], 400);
        }

        $order->update(['status' => 'confirmed']);

        if ($order->table) {
            $order->table->update(['status' => 'occupied']);
        }

        Log::info('Customer arrival confirmed', [
            'order_id' => $order->id,
            'staff_id' => $request->staff_id
        ]);

        return response()->json([
            'message' => 'Customer arrival confirmed',
            'order' => $order,
            'remaining_amount_due' => $order->remaining_amount
        ], 200);
    }
}
