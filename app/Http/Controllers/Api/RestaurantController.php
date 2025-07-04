<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Restaurant;
use Illuminate\Support\Facades\Storage;

class RestaurantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // create a query to get all restaurants
        $query = Restaurant::query();
        // add search functionality if needed
        if (request()->has('search')) {
            $search = request()->input('search');
            $query->where('name', 'like', '%' . $search . '%')
                ->orWhere('address', 'like', '%' . $search . '%')
                ->orWhere('phone', 'like', '%' . $search . '%')
                ->orWhere('email', 'like', '%' . $search . '%');
        }

        $restaurants = $query->with(['menus', 'tables'])->get();
        return response()->json($restaurants);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'description' => 'nullable|string|max:1000',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // handle file
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public'); // Save to storage/app/public/logos
            $requestData = $request->all();
            $requestData['logo'] = $path;
        } else {
            $requestData = $request->all();
        }

        $restaurant = Restaurant::create($requestData);
        return response()->json($restaurant, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        $restaurant = Restaurant::with(['menus', 'tables'])->findOrFail($id);
        return response()->json($restaurant);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
        $restaurant = Restaurant::findOrFail($id);

        $request->validate([
            'name' => 'required|sometimes|string|max:255',
            'address' => 'required|sometimes|string|max:255',
            'phone' => 'required|sometimes|string|max:20',
            'email' => 'nullable|sometimes|email|max:255',
            'description' => 'nullable|sometimes|string|max:1000',
            'logo' => 'nullable|sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($request->hasFile('logo') && $restaurant->logo) {
            Storage::disk('public')->delete($restaurant->logo);
        }

        // handle file
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public'); // Save to storage/app/public/logos
            $requestData = $request->all();
            $requestData['logo'] = $path;
        } else {
            $requestData = $request->all();
        }

        $restaurant->update($requestData);
        return response()->json($restaurant);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $restaurant = Restaurant::findOrFail($id);
        if ($restaurant->logo) {
            Storage::disk('public')->delete($restaurant->logo);
        }
        $restaurant->delete();
        return response()->json(null, 204);
    }
}
