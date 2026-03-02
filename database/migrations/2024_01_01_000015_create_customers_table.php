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
        Schema::create('customers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tenant_id');
            $table->string('name', 255);
            $table->string('phone', 20)->nullable();
            $table->string('email', 255)->nullable();
            $table->text('address')->nullable();
            $table->decimal('opening_balance', 12, 2)->default(0.00);
            $table->tinyInteger('is_active')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index('tenant_id', 'idx_customers_tenant');
            $table->index(['tenant_id', 'phone'], 'idx_customers_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
