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
        Schema::create('ai_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('model_id');
            $table->string('provider')->default('openrouter');
            $table->boolean('supports_images')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('max_tokens')->nullable();
            $table->decimal('temperature', 3, 2)->default(0.7);
            $table->text('description')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_models');
    }
};