<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_deals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id')->nullable();
            $table->string('title');
            $table->uuid('company_id')->nullable();
            $table->uuid('contact_id')->nullable();
            $table->decimal('value', 15, 2)->nullable();
            $table->string('currency', 10)->nullable();
            $table->string('stage')->nullable();
            $table->string('status')->nullable();
            $table->uuid('owner_id')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            $table->foreign('company_id')->references('id')->on('crm_companies')->nullOnDelete();
            $table->foreign('contact_id')->references('id')->on('crm_contacts')->nullOnDelete();
            $table->foreign('owner_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['tenant_id'], 'crm_deals_tenant_index');
            $table->index(['stage'], 'crm_deals_stage_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_deals');
    }
};
