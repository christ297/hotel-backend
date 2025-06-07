<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;

class BarStatsController extends Controller
{
    public function index()
    {
        // Chiffre d'affaires aujourd'hui
        $todayRevenue = Order::whereDate('created_at', Carbon::today())
            ->sum('total');

        // Nombre de commandes ce mois
        $monthlyOrders = Order::whereMonth('created_at', Carbon::now()->month)
            ->count();

        // Article le plus populaire
        $topItem = OrderItem::selectRaw('menu_items.name, sum(quantity) as total_quantity')
            ->join('menu_items', 'menu_items.id', '=', 'order_items.menu_item_id')
            ->groupBy('menu_item_id', 'menu_items.name')
            ->orderByDesc('total_quantity')
            ->first();

        // 5 articles les plus vendus
        $topItems = OrderItem::selectRaw('menu_items.name, sum(quantity) as quantity')
            ->join('menu_items', 'menu_items.id', '=', 'order_items.menu_item_id')
            ->groupBy('menu_item_id', 'menu_items.name')
            ->orderByDesc('quantity')
            ->limit(5)
            ->get();

        // 5 dernières commandes
        $recentOrders = Order::select(['id', 'total', 'created_at as date'])
            ->latest()
            ->limit(5)
            ->get();

        return response()->json([
            'todayRevenue' => $todayRevenue,
            'monthlyOrders' => $monthlyOrders,
            'topItem' => $topItem->name ?? null,
            'topItems' => $topItems,
            'recentOrders' => $recentOrders
        ]);
    }
}