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
            $table->bigIncrements('id');
            $table->string('name', 255);
            $table->string('slug', 100)->unique();
            $table->string('owner_name', 255);
            $table->string('email', 255)->unique();
            $table->string('phone', 20);
            $table->string('drug_license_no', 100)->nullable();
            $table->string('gstin', 20)->nullable();
            $table->string('address_line1', 255)->nullable();
            $table->string('address_line2', 255)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->default('Maharashtra');
            $table->string('pincode', 10)->nullable();
            $table->enum('subscription_status', ['active', 'inactive', 'trial', 'expired'])->default('trial');
            $table->string('subscription_plan', 50)->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('subscription_ends_at')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('slug', 'idx_tenants_slug');
            $table->index('subscription_status', 'idx_tenants_subscription');
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
