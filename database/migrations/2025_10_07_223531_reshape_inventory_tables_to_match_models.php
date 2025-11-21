<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Drop placeholder tables if they exist
        if (Schema::hasTable('inventory_movements')) Schema::drop('inventory_movements');
        if (Schema::hasTable('inventories'))         Schema::drop('inventories');
        if (Schema::hasTable('locations'))           Schema::drop('locations');

        // 1) inventory_items (SKU catalog)
        Schema::create('inventory_items', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();

            $t->string('sku');
            $t->string('name');
            $t->string('category')->nullable();
            $t->string('uom', 20);
            $t->integer('reorder_level')->default(0);
            $t->json('metadata')->nullable();

            $t->timestamps();

            $t->unique(['tenant_id','sku']);
            $t->index(['tenant_id','name']);
            $t->index(['tenant_id','category']);
        });

        // 2) inventory_locations (warehouses / yards)
        Schema::create('inventory_locations', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();

            $t->string('code');
            $t->string('name');

            $t->timestamps();

            $t->unique(['tenant_id','code']);
        });

        // 3) stock_movements (in/out/transfer)
        Schema::create('stock_movements', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();

            $t->foreignUuid('inventory_item_id')
              ->constrained('inventory_items')->cascadeOnDelete();

            $t->enum('type', ['in','out','transfer']);
            $t->integer('qty');

            $t->foreignUuid('from_location_id')->nullable()
              ->constrained('inventory_locations')->nullOnDelete();
            $t->foreignUuid('to_location_id')->nullable()
              ->constrained('inventory_locations')->nullOnDelete();

            $t->string('reference')->nullable();
            $t->text('note')->nullable();

            $t->timestamps();

            $t->index(['tenant_id','inventory_item_id']);
            $t->index(['tenant_id','type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('inventory_locations');
        Schema::dropIfExists('inventory_items');

        // (Optional) recreate placeholders if you really need to roll back to old shape
        Schema::create('inventories', function (Blueprint $t) {
            $t->id(); $t->timestamps();
        });
        Schema::create('locations', function (Blueprint $t) {
            $t->id(); $t->timestamps();
        });
        Schema::create('inventory_movements', function (Blueprint $t) {
            $t->id(); $t->timestamps();
        });
    }
};
