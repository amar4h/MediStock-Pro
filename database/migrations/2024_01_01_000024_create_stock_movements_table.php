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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('batch_id');
            $table->enum('movement_type', ['purchase', 'purchase_return', 'sale', 'sale_return', 'discard', 'adjustment']);
            $table->string('reference_type', 50);
            $table->unsignedBigInteger('reference_id');
            $table->integer('quantity');
            $table->integer('stock_before');
            $table->integer('stock_after');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('items');
            $table->foreign('batch_id')->references('id')->on('batches');
            $table->index(['tenant_id', 'item_id', 'created_at'], 'idx_stock_movements_item');
            $table->index(['tenant_id', 'batch_id'], 'idx_stock_movements_batch');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
