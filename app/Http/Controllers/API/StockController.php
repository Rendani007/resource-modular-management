<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class StockController extends Controller
{
    // POST /inventory/stock/in
    public function stockIn(Request $request)
    {
        $data = $request->validate([
            'inventory_item_id' => [
                'required',
                'uuid',
                Rule::exists('inventory_items','id')->where(fn($q) => $q->where('tenant_id', app('tenant_id')))
            ],
            'qty' => ['required','integer','min:1'],
            'to_location_id' => [
                'required',
                'uuid',
                Rule::exists('inventory_locations','id')->where(fn($q) => $q->where('tenant_id', app('tenant_id')))
            ],
            'reference' => ['nullable','string','max:100'],
            'note' => ['nullable','string','max:1000'],
        ]);

        $key = $request->header('Idempotency-Key') ?? sha1(json_encode($data));
        return $this->idempotent($key, function() use ($data) {
            return DB::transaction(function () use ($data) {
            $data['tenant_id'] = app('tenant_id');
            $data['type'] = 'in';
            $m = StockMovement::create($data);
            return response()->json(['status'=>'ok','data'=>$m], 201);
            });
        });
    }

    // POST /inventory/stock/out
    public function stockOut(Request $request)
    {
        $data = $request->validate([
            'inventory_item_id' => [
                'required',
                'uuid',
                Rule::exists('inventory_items','id')->where(fn($q) => $q->where('tenant_id', app('tenant_id')))
            ],
            'qty' => ['required','integer','min:1'],
            'from_location_id' => [
                'required',
                'uuid',
                 Rule::exists('inventory_locations','id')->where(fn($q) => $q->where('tenant_id', app('tenant_id')))
            ],
            'reference' => ['nullable','string','max:100'],
            'note' => ['nullable','string','max:1000'],
        ]);


        $key = $request->header('Idempotency-Key') ?? sha1(json_encode($data));
        return $this->idempotent($key, function() use ($data) {
            return DB::transaction(function () use ($data) {
            $data['tenant_id'] = app('tenant_id');
            $data['type'] = 'out';
            $m = StockMovement::create($data);
            return response()->json(['status'=>'ok','data'=>$m], 201);
            });
        });
    }

    // POST /inventory/stock/transfer
    public function transfer(Request $request)
    {
        $data = $request->validate([
            'inventory_item_id' => [
                'required','uuid',
                 Rule::exists('inventory_items','id')->where(fn($q) => $q->where('tenant_id', app('tenant_id')))
            ],
            'qty'               => ['required','integer','min:1'],
            'from_location_id'  => [
                'required',
                'uuid',
                'different:to_location_id',
                Rule::exists('inventory_locations','id')->where(fn($q) => $q->where('tenant_id', app('tenant_id')))
            ],
            'to_location_id'    => [
                'required',
                'uuid',
                Rule::exists('inventory_locations','id')->where(fn($q) => $q->where('tenant_id', app('tenant_id')))
            ],
            'reference'         => ['nullable','string','max:100'],
            'note'              => ['nullable','string','max:1000'],
        ]);


        $key = $request->header('Idempotency-Key') ?? sha1(json_encode($data));
        return $this->idempotent($key, function() use ($data) {
            return DB::transaction(function () use ($data) {
            $data['tenant_id'] = app('tenant_id');
            $data['type'] = 'transfer';
            $m = StockMovement::create($data);
            return response()->json(['status'=>'ok','data'=>$m], 201);
            });
        });
    }

    // (nice-to-have) GET /inventory/items/{item}/stock
    // returns totals by location without storing a "stock" table
    public function stockForItem(InventoryItem $item)
    {
        $movements = $item->movements()
            ->with(['from:id,code,name', 'to:id,code,name'])
            ->get(['id','type','qty','from_location_id','to_location_id']);

        $byLocation = [];

        foreach ($movements as $m) {
            if ($m->type !== 'in' && $m->from_location_id) {
                $k = $m->from_location_id;
                $byLocation[$k] = ($byLocation[$k] ?? 0) - $m->qty;
            }
            if ($m->type !== 'out' && $m->to_location_id) {
                $k = $m->to_location_id;
                $byLocation[$k] = ($byLocation[$k] ?? 0) + $m->qty;
            }
        }

        // decorate with location code/name
        $locations = InventoryLocation::whereIn('id', array_keys($byLocation))->get(['id','code','name']);
        $out = $locations->map(fn($loc) => [
            'location_id' => $loc->id,
            'code'        => $loc->code,
            'name'        => $loc->name,
            'qty'         => $byLocation[$loc->id] ?? 0,
        ])->values();

        return response()->json(['status'=>'ok','data'=>[
            'item_id' => $item->id,
            'stock_by_location' => $out,
            'total' => array_sum($byLocation),
        ]]);
    }

    //Prevent issuing/transferring more stock than a location holds.
    private function ensureSufficientStock(string $itemId, string $fromLocationId, int $qty): void{

        $in = StockMovement::where('inventory_item_id', $itemId)
        ->where('to_location_id', $fromLocationId)
        ->whereIn('type',['in','transfer'])
        ->sum('qty');

        $out = StockMovement::where('inventory_item_id', $itemId)
        ->where('from_location_id', $fromLocationId)
        ->whereIn('type', ['out','transfer'])
        ->sum('qty');

        $available = $in - $out;

        if ($available < $qty) {
            abort(response()->json([
                'error'      => 'Insufficient stock at source location',
                'available'  => $available,
                'requested'  => $qty,
                'location_id'=> $fromLocationId,
                'item_id'    => $itemId,
            ], 409));
        }
    }

    //ignore if theres double attempts on connection
    private function idempotent(string $key, \Closure $callback, int $ttlSeconds = 120){
        $cacheKey = 'idem:'.$key;
        if (Cache::has($cacheKey)) {
            // already processed recently
            abort(response()->json(['status'=>'ok','idempotent'=>true], 200));
        }
        $result = $callback();
        Cache::put($cacheKey, 1, $ttlSeconds);
        return $result;
    }
}
