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
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('purchase_id');
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->string('batch_number', 100);
            $table->date('expiry_date');
            $table->integer('quantity');
            $table->integer('free_quantity')->default(0);
            $table->decimal('mrp', 10, 2);
            $table->decimal('purchase_price', 10, 2);
            $table->decimal('selling_price', 10, 2);
            $table->decimal('gst_percent', 5, 2)->default(0);
            $table->decimal('gst_amount', 10, 2)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('purchase_id')->references('id')->on('purchases')->onDelete('cascade');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('items');
            $table->foreign('batch_id')->references('id')->on('batches');
            $table->index('purchase_id', 'idx_purchase_items_purchase');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};
