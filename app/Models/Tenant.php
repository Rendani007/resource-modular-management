<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    //
    use HasFactory; use UsesUuid;

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'industry',
        'plan',
        'enabled_modules',
        'max_users',
        'is_active',
        'admin_email',
        'phone',
        'address',
        'settings',
        'logo_path',
        'primary_color',
        'trial_ends_at',
        'last_activity_at',
    ];

    protected $casts = [
        'enabled_modules' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
        'trial_ends_at' => 'datetime',
        'last_activity_at' => 'datetime',
    ];

    // get all users belonging to this tenant
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    // get the tenant admin users
    public function admins(): HasMany{
        return $this->users()->where('is_tenant_admin', true);
    }

    // check if the tenant has a specific module enabled
    public function hasModule(string $module): bool
    {
        return in_array($module, $this->enabled_modules ?? []);
    }

    //check if tenant is on trial
    public function onTrial(): bool{
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    //Check if tenant's trial has expired
     public function trialExpired(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isPast();
    }

    //get tenant's industry specific modules
     public function getAvailableModules(): array
    {
        $moduleMapping = [
            'mining' => ['inventory', 'fleet', 'maintenance', 'safety'],
            'oil_gas' => ['assets', 'maintenance', 'procurement', 'field_service'],
            'manufacturing' => ['production', 'suppliers', 'quality', 'maintenance'],
            'logistics' => ['fleet', 'warehouse', 'routes', 'delivery'],
            'construction' => ['equipment', 'contractors', 'materials', 'costs'],
            'utilities' => ['assets', 'maintenance', 'compliance', 'procurement'],
            'financial_services' => ['it_assets', 'procurement', 'vendor_risk', 'compliance']
        ];

        return $moduleMapping[$this->industry] ?? [];
    }

    /**
     * Scope for active tenants
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Update tenant's last activity
     */
    public function updateLastActivity(): void
    {
        $this->update(['last_activity_at' => now()]);
    }
}
