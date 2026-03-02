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
        Schema::create('sales', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('invoice_number', 50);
            $table->date('invoice_date');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('gst_amount', 12, 2)->default(0);
            $table->decimal('item_discount_total', 12, 2)->default(0);
            $table->decimal('invoice_discount', 12, 2)->default(0);
            $table->decimal('roundoff', 5, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->enum('payment_mode', ['cash', 'credit', 'partial', 'upi'])->default('cash');
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('balance_amount', 12, 2)->default(0);
            $table->enum('status', ['completed', 'returned', 'partial_return'])->default('completed');
            $table->string('doctor_name', 255)->nullable();
            $table->string('patient_name', 255)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers');
            $table->foreign('created_by')->references('id')->on('users');
            $table->unique(['tenant_id', 'invoice_number'], 'uq_sales_tenant_invoice');
            $table->index(['tenant_id', 'invoice_date'], 'idx_sales_tenant_date');
            $table->index(['tenant_id', 'customer_id'], 'idx_sales_customer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
