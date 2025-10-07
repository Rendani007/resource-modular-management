<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

trait UsesUuid
{
    use HasUuids;

    /**
     * called by Eloquent when the model is constructed.
     * No property re-declarations = no conflicts with the base Model.
     */
    public function initializeUsesUuid(): void
    {
        $this->setKeyType('string'); // sets $keyType = 'string'
        $this->incrementing = false; // disables auto-increment
    }

    // Optional: customize which columns get UUIDs (defaults to 'id')
    public function uniqueIds(): array
    {
        return ['id'];
    }
}
