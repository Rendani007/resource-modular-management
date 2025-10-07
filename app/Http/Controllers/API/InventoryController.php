<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreItemRequest;
use App\Http\Requests\Inventory\UpdateItemRequest;
use App\Models\InventoryItem;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    //Get inventory items
    public function index(Request $request){
        $q = InventoryItem::query();

        if($s = $request->get('q')) {
            $q->where(function ($x) use ($s) {
                $x->where('sku','like',"%{$s}%")
                  ->orWhere('name','like',"%{$s}%");
            });
        }
         if ($cat = $request->get('category')) $q->where('category', $cat);

        $items = $q->orderBy('name')->paginate($request->integer('per_page', 20));

        return response()->json([
            'status' => 'ok',
            'data'   => $items,
        ]);
    }

    // POST inventory items
    public function store(StoreItemRequest $request)
    {
        $item = InventoryItem::create($request->validated());
        return response()->json(['status'=>'ok','data'=>$item], 201);
    }

    // GET /inventory/items/{item}
    public function show(InventoryItem $item)
    {
        return response()->json(['status'=>'ok','data'=>$item]);
    }

    // PUT /inventory/items/{item}
    public function update(UpdateItemRequest $request, InventoryItem $item)
    {
        $item->update($request->validated());
        return response()->json(['status'=>'ok','data'=>$item]);
    }

    // DELETE /inventory/items/{item}
    public function destroy(InventoryItem $item)
    {
        $item->delete();
        return response()->json(['status'=>'ok','data'=>['deleted'=>true]]);
    }

}
