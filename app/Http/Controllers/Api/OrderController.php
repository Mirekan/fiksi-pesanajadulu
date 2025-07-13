<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Midtrans\Config;
use Midtrans\Snap;

class OrderController extends Controller
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
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $userOrders = Order::where('user_id', $request->user()->id)
            ->with(['user', 'table', 'orderItems.menu', 'payment'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'orders' => $userOrders
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'table_id' => 'required|exists:tables,id',
            'amount' => 'required|numeric|min:0',
            'reservation_time' => 'required|date',
            'order_items' => 'required|array',
            'order_items.*.menu_id' => 'required|exists:menus,id',
            'order_items.*.quantity' => 'required|integer|min:1',
            'order_items.*.price' => 'required|numeric|min:0',
        ]);

        $orderItems = $request->input('order_items');

        // Check stock availability for all items before creating order
        foreach ($orderItems as $item) {
            $menu = Menu::find($item['menu_id']);
            if (!$menu->isInStock($item['quantity'])) {
                return response()->json([
                    'message' => 'Insufficient stock for menu item: ' . $menu->name,
                    'available_stock' => $menu->stock,
                    'requested_quantity' => $item['quantity']
                ], 400);
            }
        }

        // Calculate total amount (request amount is 50%, so multiply by 2)
        $totalAmount = $request->input('amount') * 2;

        // Create order and reserve stock in a transaction
        $order = DB::transaction(function () use ($request, $orderItems, $totalAmount) {
            $order = Order::create([
                'user_id' => $request->input('user_id'),
                'table_id' => $request->input('table_id'),
                'amount' => $totalAmount, // Use calculated total amount
                'status' => 'pending',
                'reservation_time' => $request->input('reservation_time'),
            ]);

            // Create order items and temporarily reserve stock
            foreach ($orderItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'menu_id' => $item['menu_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);

                // Temporarily reserve stock (decrement) - will be restored if payment fails
                $menu = Menu::find($item['menu_id']);
                $menu->decrementStock($item['quantity']);
            }

            return $order;
        });

        // Use the amount from request (already 50% from client)
        $paymentAmount = $request->input('amount');

        // Prepare Midtrans transaction data using the payment amount from request
        $midtransData = [
            'transaction_details' => [
                'order_id' => $order->id,
                'gross_amount' => $paymentAmount, // Use amount from request (already 50%)
            ],
            'customer_details' => [
                'first_name' => $request->user()->name,
                'email' => $request->user()->email,
            ],
            'item_details' => [
                [
                    'id' => 'advance_payment_' . $order->id,
                    'price' => $paymentAmount,
                    'quantity' => 1,
                    'name' => 'Advance Payment (50%) for Order #' . $order->id,
                ]
            ],
            'custom_field1' => 'advance_payment', // Mark as advance payment
            'custom_field2' => 'remaining_amount:' . ($order->amount - $paymentAmount), // Store remaining amount info
        ];

        // Create Midtrans transaction
        $snapUrl = Snap::getSnapUrl($midtransData);

        return response()->json([
            'snap_url' => $snapUrl,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $order = Order::with(['user', 'table', 'orderItems.menu', 'payment'])
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json([
            'order' => $order
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
