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
        Schema::create('voice_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('language', 20)->default('auto');
            $table->string('voice', 50)->default('alloy');
            $table->string('ai_model', 100)->default('gpt-4o-mini');
            $table->decimal('temperature', 2, 1)->default(0.7);
            $table->boolean('auto_play')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voice_settings');
    }
};
