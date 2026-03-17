<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('crm_stages', function (Blueprint $table) {
			$table->uuid('id')->primary();
			$table->string('tenant_id')->nullable();
			$table->string('name');
			$table->integer('sort_order')->default(0);
			$table->softDeletes();
			$table->timestamps();

			$table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
			$table->index(['tenant_id'], 'crm_stages_tenant_index');
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('crm_stages');
	}
};
