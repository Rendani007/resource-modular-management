<?php

use App\Models\Tenant;

/**
 * Helper function to get current tenant
 * Usage: tenant() or app('tenant')
 */
if (!function_exists('tenant')) {
    function tenant(): ?Tenant {
        return app('tenant');
    }
}
