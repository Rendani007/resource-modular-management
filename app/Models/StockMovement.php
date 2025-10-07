<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    //
    use HasFactory, UsesUuid, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'inventory_item_id',
        'type',
        'qty',
        'from_location_id',
        'to_location_id',
        'reference',
        'note',
    ];

    public function item(): BelongsTo {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
    public function from(): BelongsTo {
        return $this->belongsTo(InventoryLocation::class, 'from_location_id');
    }
    public function to(): BelongsTo   {
        return $this->belongsTo(InventoryLocation::class, 'to_location_id');
    }

}
