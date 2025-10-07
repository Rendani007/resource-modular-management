<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // Correct trait for API tokens
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles,UsesUuid; // Ensure correct trait usage
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'job_title',
        'department',
        'employee_id',
        'bio',
        'avatar_path',
        'is_active',
        'is_tenant_admin',
        'permissions',
        'last_login_at',
        'last_login_ip',
        'password_changed_at',
        'must_change_password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'permissions' => 'array',
        'is_active' => 'boolean',
        'is_tenant_admin' => 'boolean',
        'must_change_password' => 'boolean',
        'last_login_at' => 'datetime',
        'password_changed_at' => 'datetime',
        'locked_until' => 'datetime',
    ];

    //get the tenant this user belongs to
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    //get user full name
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    //check if user account is locked
    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    //lock user account for specified minutes
    public function lockAccount(int $minutes = 30): void
    {
        $this->update([
            'locked_until' => now()->addMinutes($minutes),
            'login_attempts' => 0
        ]);
    }

    //increment failed login attemts
    public function incrementLoginAttempts(): void
    {
        $attempts = $this->login_attempts + 1;
        $this->update(['login_attempts' => $attempts]);

        // Lock account after 5 failed attempts
        if ($attempts >= 5) {
            $this->lockAccount();
        }
    }

    /**
     * Reset login attempts on successful login
     */
    public function resetLoginAttempts(): void
    {
        $this->update([
            'login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ]);
    }

    /**
     * Check if user has a specific permission within their tenant
     */
    public function hasPermissionInTenant(string $permission): bool
    {
        // Tenant admins have all permissions
        if ($this->is_tenant_admin) {
            return true;
        }

        // Check role-based permissions (Spatie package)
        if ($this->hasPermissionTo($permission)) {
            return true;
        }

        // Check custom permissions
        $customPermissions = $this->permissions ?? [];
        return in_array($permission, $customPermissions);
    }

    /**
     * Scope to active users only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to users of a specific tenant
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

}
