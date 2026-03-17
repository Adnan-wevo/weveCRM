<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('source')->nullable();
            $table->string('status')->default('new')->index();
            $table->foreignUuid('contact_id')->nullable()->index();
            $table->foreignUuid('owner_id')->nullable()->index();
            $table->integer('score')->nullable()->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
