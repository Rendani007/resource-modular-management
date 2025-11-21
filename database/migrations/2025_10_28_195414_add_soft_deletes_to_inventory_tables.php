<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Adds a timezone-aware deleted_at column
        Schema::table('inventory_items', function (Blueprint $t) {
            $t->softDeletesTz();
        });

        Schema::table('inventory_locations', function (Blueprint $t) {
            $t->softDeletesTz();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $t) {
            $t->dropSoftDeletesTz();
        });

        Schema::table('inventory_locations', function (Blueprint $t) {
            $t->dropSoftDeletesTz();
        });
    }
};
