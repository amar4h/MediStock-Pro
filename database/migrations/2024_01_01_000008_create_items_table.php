<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('manufacturer_id')->nullable();
            $table->string('name', 255);
            $table->string('composition', 500)->nullable();
            $table->string('hsn_code', 20)->nullable();
            $table->decimal('gst_percent', 5, 2)->default(0.00);
            $table->decimal('default_margin', 5, 2)->default(0.00);
            $table->string('barcode', 100)->nullable();
            $table->string('unit', 50)->default('strip');
            $table->string('schedule', 10)->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
            $table->foreign('manufacturer_id')->references('id')->on('manufacturers')->onDelete('set null');
            $table->index('tenant_id', 'idx_items_tenant');
            $table->index(['tenant_id', 'barcode'], 'idx_items_barcode');
            $table->index(['tenant_id', 'name'], 'idx_items_name');
        });

        // Add FULLTEXT index via raw statement (not supported by Blueprint)
        DB::statement('ALTER TABLE items ADD FULLTEXT INDEX ft_items_search (name, composition)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
