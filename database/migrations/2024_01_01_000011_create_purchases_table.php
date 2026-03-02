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
        Schema::create('purchases', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('supplier_id');
            $table->string('invoice_number', 100);
            $table->date('invoice_date');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('gst_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->enum('payment_mode', ['cash', 'credit', 'partial'])->default('cash');
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('balance_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('supplier_id')->references('id')->on('suppliers');
            $table->foreign('created_by')->references('id')->on('users');
            $table->unique(['tenant_id', 'supplier_id', 'invoice_number'], 'uq_purchases_tenant_invoice');
            $table->index(['tenant_id', 'invoice_date'], 'idx_purchases_tenant_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
