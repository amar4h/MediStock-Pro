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
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('role_id');
            $table->string('name', 255);
            $table->string('email', 255);
            $table->string('phone', 20)->nullable();
            $table->string('password', 255);
            $table->tinyInteger('is_active')->default(1);
            $table->timestamp('last_login_at')->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('roles');
            $table->unique(['tenant_id', 'email'], 'uq_users_tenant_email');
            $table->index('tenant_id', 'idx_users_tenant');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
