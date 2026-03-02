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
        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('purchase_id')->nullable();
            $table->decimal('amount', 12, 2);
            $table->enum('payment_mode', ['cash', 'upi', 'bank_transfer', 'cheque'])->default('cash');
            $table->date('payment_date');
            $table->string('reference_no', 100)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('supplier_id')->references('id')->on('suppliers');
            $table->foreign('purchase_id')->references('id')->on('purchases');
            $table->foreign('created_by')->references('id')->on('users');
            $table->index(['tenant_id', 'payment_date'], 'idx_supplier_payments_tenant');
            $table->index(['tenant_id', 'supplier_id'], 'idx_supplier_payments_supplier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_payments');
    }
};
