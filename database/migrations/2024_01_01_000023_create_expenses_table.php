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
        Schema::create('expenses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('expense_category_id');
            $table->decimal('amount', 12, 2);
            $table->date('expense_date');
            $table->text('description')->nullable();
            $table->enum('payment_mode', ['cash', 'upi', 'bank_transfer', 'cheque'])->default('cash');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('expense_category_id')->references('id')->on('expense_categories');
            $table->foreign('created_by')->references('id')->on('users');
            $table->index(['tenant_id', 'expense_date'], 'idx_expenses_tenant_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
