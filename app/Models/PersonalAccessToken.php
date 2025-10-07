<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Laravel\Sanctum\PersonalAccessToken as SanctumToken;

class PersonalAccessToken extends SanctumToken
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    // (optional) explicit list of uuid columns
    public function uniqueIds(): array
    {
        return ['id'];
    }
}
