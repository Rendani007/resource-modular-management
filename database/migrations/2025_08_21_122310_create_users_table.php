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
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();

            //tenant relationship - critical for multitenancy
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();

            //basic user info
            $table->string('email');
            $table->unique(['tenant_id', 'email']);
            $table->string('first_name');
            $table->string('last_name');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            // Profile information
            $table->string('phone')->nullable();
            $table->string('job_title')->nullable();
            $table->string('department')->nullable();
            $table->string('employee_id')->nullable();
            $table->text('bio')->nullable();
            $table->string('avatar_path')->nullable();

            // Access control
            $table->boolean('is_active')->default(true);
            $table->boolean('is_tenant_admin')->default(false); // Can manage tenant settings
            $table->json('permissions')->default('{}'); // Additional custom permissions

            // Security & tracking
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip')->nullable();
            $table->integer('login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();

            // Password security
            $table->timestamp('password_changed_at')->nullable();
            $table->boolean('must_change_password')->default(false);

            // API access
            $table->rememberToken();
            $table->timestamps();

            // Indexes for performance and security
            $table->unique(['tenant_id', 'email']); // Email unique per tenant
            $table->index(['tenant_id', 'is_active']);
            $table->index('employee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
