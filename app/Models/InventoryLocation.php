<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryLocation extends Model
{
    //
    use HasFactory, UsesUuid, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'code',
        'name'
    ];

    public function inboundMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'to_location_id');
    }

    public function outboundMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'from_location_id');
    }

}
