<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // search
    public function index(Request $request)
    {
        $query = Menu::query();

        // Filter by name
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->input('name') . '%');
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        // Filter by price
        if ($request->filled('price')) {
            $query->where('price', $request->input('price'));
        }

        // Filter by stock availability
        if ($request->boolean('available_only')) {
            $query->available(); // Only show items in stock
        }

        // Include stock status in response
        $menus = $query->with('restaurant')->get()->map(function ($menu) {
            $menu->is_available = $menu->isInStock();
            $menu->is_out_of_stock = $menu->isOutOfStock();
            return $menu;
        });

        return response()->json($menus);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'restaurant_id' => 'required|exists:restaurants,id',
            'price' => 'required|numeric|min:0',
        ]);

        $menu = Menu::create($request->all());
        return response()->json($menu, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $menu = Menu::findOrFail($id);
        return response()->json($menu);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required|sometimes|string|max:255',
            'category' => 'required|sometimes|string|max:255',
            'description' => 'nullable|sometimes|string|max:1000',
            'restaurant_id' => 'required|sometimes|exists:restaurants,id',
            'price' => 'required|sometimes|numeric|min:0',
        ]);

        $menu = Menu::findOrFail($id);
        $menu->update($request->all());
        return response()->json($menu);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $menu = Menu::findOrFail($id);
        $menu->delete();
        return response()->json(null, 204);
    }
}
