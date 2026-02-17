<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('challenges', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description');
            $table->string('type', 20);
            $table->string('difficulty', 10);
            $table->string('criteria_type');
            $table->unsignedInteger('criteria_value');
            $table->unsignedInteger('xp_reward')->default(0);
            $table->unsignedInteger('bonus_logs_reward')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('challenges');
    }
};
