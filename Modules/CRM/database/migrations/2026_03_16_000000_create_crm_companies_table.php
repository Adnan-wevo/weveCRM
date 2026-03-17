<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_companies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id')->nullable();
            $table->string('name');
            $table->string('industry')->nullable();
            $table->string('website')->nullable();
            $table->string('phone')->nullable();
            $table->json('address')->nullable();
            $table->uuid('owner_id')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            $table->foreign('owner_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['tenant_id'], 'crm_companies_tenant_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_companies');
    }
};
