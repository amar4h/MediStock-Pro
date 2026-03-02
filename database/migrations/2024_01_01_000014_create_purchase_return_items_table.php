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
        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('purchase_return_id');
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('batch_id');
            $table->integer('quantity');
            $table->decimal('purchase_price', 10, 2);
            $table->decimal('total_amount', 10, 2);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('purchase_return_id')->references('id')->on('purchase_returns')->onDelete('cascade');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('items');
            $table->foreign('batch_id')->references('id')->on('batches');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
    }
};
