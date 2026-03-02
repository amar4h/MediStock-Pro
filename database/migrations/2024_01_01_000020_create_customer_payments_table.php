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
        Schema::create('customer_payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->decimal('amount', 12, 2);
            $table->enum('payment_mode', ['cash', 'upi', 'bank_transfer', 'cheque'])->default('cash');
            $table->date('payment_date');
            $table->string('reference_no', 100)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers');
            $table->foreign('sale_id')->references('id')->on('sales');
            $table->foreign('created_by')->references('id')->on('users');
            $table->index(['tenant_id', 'payment_date'], 'idx_customer_payments_tenant');
            $table->index(['tenant_id', 'customer_id'], 'idx_customer_payments_customer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_payments');
    }
};
