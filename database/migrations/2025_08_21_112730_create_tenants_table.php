<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // basic tenant information
            $table->string('name');//company name
            $table->string('slug')->unique();//url-friendly identifier
            $table->string('domain')->nullable()->unique();// custom domain for tenant

            //industry configuration
            $table->enum('industry', [
                'mining',
                'oil_gas',
                'manufacturing',
                'logistics',
                'construction',
                'utilities',
                'financial_services'
            ]);

            //subscriptions & billing
            $table->enum('plan', ['basic', 'professional', 'enterprise'])->default('basic');
            $table->json('enabled_modules')->default('[]');//which modules this tenant can access
            $table->integer('max_users')->default(5);//maximum number of users allowed
            $table->boolean('is_active')->default(true);//is the tenant currently active?

            //contact information
            $table->string('admin_email');
            $table->string('phone')->nullable();
            $table->text('address')->nullable();

            //settings and customizations
            $table->json('settings')->default('{}');
            $table->string('logo_path')->nullable();
            $table->string('primary_color')->default('#3B82F6');

            //security
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            //indexes for performance
            $table->index('slug');
            $table->index('domain');
            $table->index('industry');
            $table->index('is_active');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
