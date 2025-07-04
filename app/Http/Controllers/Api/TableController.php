<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Table;

class TableController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    // search
    public function index(Request $request)
    {
        $query = Table::query();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Search by capacity
        if ($request->filled('capacity')) {
            $query->where('capacity', 'like', '%' . $request->input('capacity') . '%');
        }

        $tables = $query->with('restaurant')->get();
        return response()->json($tables);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'capacity' => 'required|integer|min:1',
            'status' => 'required|string|in:available,occupied,reserved',
        ]);

        $table = Table::create($request->all());
        return response()->json($table, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $table = Table::with('restaurant')->findOrFail($id);
        return response()->json($table);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'capacity' => 'required|integer|min:1',
            'status' => 'required|string|in:available,occupied,reserved',
        ]);

        $table = Table::findOrFail($id);
        $table->update($request->all());
        return response()->json($table);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $table = Table::findOrFail($id);
        $table->delete();
        return response()->json(null, 204);
    }
}
