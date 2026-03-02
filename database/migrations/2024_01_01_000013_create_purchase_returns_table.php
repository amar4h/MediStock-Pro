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
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('purchase_id');
            $table->string('return_number', 50);
            $table->date('return_date');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('purchase_id')->references('id')->on('purchases');
            $table->foreign('created_by')->references('id')->on('users');
            $table->index(['tenant_id', 'return_date'], 'idx_purchase_returns_tenant');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_returns');
    }
};
