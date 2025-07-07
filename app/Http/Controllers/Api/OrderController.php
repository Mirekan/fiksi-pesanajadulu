<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
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
    public function index()
    {
        //
        $user = auth('api')->user();
        $userOrders = Order::where('user_id', $user->id)
            ->with(['user', 'table', 'orderItems.product'])
            ->get();

        return response()->json($userOrders, 200);
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
        ]);

        $order = Order::create([
            'user_id' => $request->input('user_id'),
            'table_id' => $request->input('table_id'),
            'amount' => $request->input('amount'),
            'status' => 'pending',
            'reservation_time' => $request->input('reservation_time'),
        ]);

        $orderItems = $request->input('order_items');

        foreach ($orderItems as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
            ]);
        }

        $items = OrderItem::where('order_id', $order->id)->get();

        // Prepare Midtrans transaction data
        $midtransData = [
            'transaction_details' => [
                'order_id' => $order->id,
                'gross_amount' => $order->amount,
            ],
            'customer_details' => [
                'first_name' => $request->user()->name,
                'email' => $request->user()->email,
            ],
            'item_details' => $items->map(function ($item) {
                return [
                    'id' => $item->product_id,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                    'name' => $item->product->name,
                ];
            }),
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
    public function show(string $id)
    {
        //
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
