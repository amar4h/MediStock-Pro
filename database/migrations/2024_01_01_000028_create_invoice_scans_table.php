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
        Schema::create('invoice_scans', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('purchase_id')->nullable();
            $table->string('image_path', 500);
            $table->enum('status', ['processing', 'completed', 'partial', 'failed'])->default('processing');
            $table->longText('raw_ocr_text')->nullable();
            $table->decimal('ocr_confidence', 5, 4)->nullable();
            $table->json('extracted_data')->nullable();
            $table->json('warnings')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('processing_ms')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('purchase_id')->references('id')->on('purchases')->onDelete('set null');
            $table->index(['tenant_id', 'created_at'], 'idx_invoice_scans_tenant');
            $table->index(['tenant_id', 'status'], 'idx_invoice_scans_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_scans');
    }
};
