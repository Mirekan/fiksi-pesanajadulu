<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    //
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
}
