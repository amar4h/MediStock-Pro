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
        Schema::create('stock_discards', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('batch_id');
            $table->integer('quantity');
            $table->enum('reason', ['expired', 'damaged', 'lost', 'other']);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->date('discard_date');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('items');
            $table->foreign('batch_id')->references('id')->on('batches');
            $table->foreign('created_by')->references('id')->on('users');
            $table->index(['tenant_id', 'discard_date'], 'idx_stock_discards_tenant');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_discards');
    }
};
