<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index()
    {
        try {
            if (!Auth::user()->is_admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            $orders = Order::with(['orderItems', 'orderItems.menuItem'])
                         ->latest()
                         ->get();

            return response()->json([
                'success' => true,
                'data' => $orders
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erreur récupération commandes:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des commandes'
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'room_number' => 'required|integer',
                'status' => 'required|in:pending,preparing,ready,delivered',
                'total' => 'required|numeric|min:0',
                'items' => 'required|array|min:1',
                'items.*.menu_item_id' => 'required|exists:menu_items,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.unit_price' => 'required|numeric|min:0'
            ]);

            $order = Order::create([
                'room_number' => $validated['room_number'],
                'status' => $validated['status'],
                'total' => $validated['total']
            ]);

            foreach ($validated['items'] as $item) {
                $order->orderItems()->create([
                    'menu_item_id' => $item['menu_item_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price']
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Commande créée avec succès',
                'data' => $order->load('orderItems')
            ], 201);

        } catch (\Exception $e) {
            Log::error('Erreur création commande:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la commande',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $order = Order::with('orderItems')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $order
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erreur récupération commande:', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Commande non trouvée'
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            if (!Auth::user()->is_admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            $order = Order::findOrFail($id);

            $validated = $request->validate([
                'status' => 'required|in:pending,preparing,ready,delivered',
            ]);

            $order->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Statut mis à jour avec succès',
                'data' => $order->fresh()
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erreur mise à jour commande:', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la commande'
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            if (!Auth::user()->is_admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            $order = Order::findOrFail($id);
            $order->orderItems()->delete();
            $order->delete();

            return response()->json([
                'success' => true,
                'message' => 'Commande supprimée avec succès'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erreur suppression commande:', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la commande'
            ], 500);
        }
    }
}