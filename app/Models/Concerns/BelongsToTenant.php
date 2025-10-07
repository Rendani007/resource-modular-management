<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::creating(function ($model) {
            if (empty($model->tenant_id) && app()->bound('tenant_id')) {
                $model->tenant_id = app('tenant_id');
            }
        });

        static::addGlobalScope('tenant', function (Builder $builder) {
            if (app()->bound('tenant_id')) {
                $builder->where($builder->qualifyColumn('tenant_id'), app('tenant_id'));
            }
        });
    }
}
