<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreLocationRequest;
use App\Http\Requests\Inventory\UpdateLocationRequest;
use App\Models\InventoryLocation;
use Illuminate\Http\Request;

class InventoryLocationController extends Controller
{
    // GET /inventory/locations
    public function index(Request $request)
    {
        $q = InventoryLocation::query();

        if ($s = $request->get('q')) {
            $q->where(function ($x) use ($s) {
                $x->where('code', 'like', "%{$s}%")
                  ->orWhere('name', 'like', "%{$s}%");
            });
        }

        $locations = $q->orderBy('code')->paginate($request->integer('per_page', 20));

        return response()->json(['status' => 'ok', 'data' => $locations]);
    }

    // POST /inventory/locations
    public function store(StoreLocationRequest $request)
    {
        $loc = InventoryLocation::create($request->validated());
        return response()->json(['status' => 'ok', 'data' => $loc], 201);
    }

    // GET /inventory/locations/{location}
    public function show(InventoryLocation $location)
    {
        return response()->json(['status' => 'ok', 'data' => $location]);
    }

    // PUT /inventory/locations/{location}
    public function update(UpdateLocationRequest $request, InventoryLocation $location)
    {
        $location->update($request->validated());
        return response()->json(['status' => 'ok', 'data' => $location]);
    }

    // DELETE /inventory/locations/{location}
    public function destroy(InventoryLocation $location)
    {
        $location->delete();
        return response()->json(['status' => 'ok', 'data' => ['deleted' => true]]);
    }
}
