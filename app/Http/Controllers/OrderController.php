<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('items.menuItem')->latest()->get();
        
        return response()->json($orders);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'roomNumber' => 'required|integer',
            'status' => 'required|in:pending,preparing,ready,delivered',
            'items' => 'required|array|min:1',
            'items.*.menu_item_id' => 'required|exists:menu_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'total' => 'required|numeric|min:0'
        ]);

        $order = Order::create([
            'room_number' => $validated['roomNumber'],
            'status' => $validated['status'],
            'total' => $validated['total']
        ]);

        foreach ($validated['items'] as $item) {
            $menuItem = MenuItem::find($item['menu_item_id']);
            
            OrderItem::create([
                'order_id' => $order->id,
                'menu_item_id' => $item['menu_item_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $menuItem->price
            ]);
        }

        return response()->json($order->load('items.menuItem'), 201);
    }

    public function updateStatus(Order $order, Request $request)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,preparing,ready,delivered'
        ]);

        $order->update(['status' => $validated['status']]);

        return response()->json($order);
    }

    public function destroy(Order $order)
    {
        $order->delete();
        
        return response()->json(null, 204);
    }
}