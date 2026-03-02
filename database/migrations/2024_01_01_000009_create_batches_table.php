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
        Schema::create('batches', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('item_id');
            $table->string('batch_number', 100);
            $table->date('expiry_date');
            $table->decimal('mrp', 10, 2);
            $table->decimal('purchase_price', 10, 2);
            $table->decimal('selling_price', 10, 2);
            $table->integer('stock_quantity')->default(0);
            $table->tinyInteger('is_active')->default(1);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
            $table->index(['tenant_id', 'item_id'], 'idx_batches_item');
            $table->index(['tenant_id', 'expiry_date'], 'idx_batches_expiry');
            $table->index(['tenant_id', 'item_id', 'expiry_date', 'stock_quantity'], 'idx_batches_fifo');
            $table->unique(['tenant_id', 'item_id', 'batch_number'], 'uq_batches_tenant_item_batch');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
