<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_calls', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id')->nullable();
            $table->foreignUuid('contact_id')->nullable();
            $table->foreignUuid('user_id')->nullable();
            $table->string('direction', 20)->default('outbound');
            $table->unsignedInteger('duration')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('called_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            $table->foreign('contact_id')->references('id')->on('contacts')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['tenant_id'], 'crm_calls_tenant_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_calls');
    }
};
