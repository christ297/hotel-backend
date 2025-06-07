<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MenuItemController extends Controller
{
    public function index()
    {
        $menuItems = MenuItem::all();
        
        return response()->json($menuItems);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'available' => 'required|boolean',
            'image' => 'nullable|image|max:2048'
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('menu-items', 'public');
        }

        $menuItem = MenuItem::create([
            'name' => $validated['name'],
            'category' => $validated['category'],
            'price' => $validated['price'],
            'description' => $validated['description'],
            'available' => $validated['available'],
            'image' => $imagePath
        ]);

        return response()->json($menuItem, 201);
    }

    public function update(Request $request, MenuItem $menuItem)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'available' => 'required|boolean',
            'image' => 'nullable|image|max:2048'
        ]);

        $imagePath = $menuItem->image;
        if ($request->hasFile('image')) {
            // Supprimer l'ancienne image si elle existe
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
            $imagePath = $request->file('image')->store('menu-items', 'public');
        }

        $menuItem->update([
            'name' => $validated['name'],
            'category' => $validated['category'],
            'price' => $validated['price'],
            'description' => $validated['description'],
            'available' => $validated['available'],
            'image' => $imagePath
        ]);

        return response()->json($menuItem);
    }

    public function destroy(MenuItem $menuItem)
    {
        if ($menuItem->image) {
            Storage::disk('public')->delete($menuItem->image);
        }
        
        $menuItem->delete();
        
        return response()->json(null, 204);
    }
}