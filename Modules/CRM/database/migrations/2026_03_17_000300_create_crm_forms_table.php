<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_forms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id')->nullable();
            $table->string('name');
            $table->json('schema')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignUuid('owner_id')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            $table->foreign('owner_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['tenant_id'], 'crm_forms_tenant_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_forms');
    }
};
