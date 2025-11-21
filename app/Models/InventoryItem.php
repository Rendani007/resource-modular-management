<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    //
    use HasFactory, UsesUuid, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'sku',
        'name',
        'category',
        'uom',
        'reorder_level',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'inventory_item_id');
    }
}
