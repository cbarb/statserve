<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->string('milestone_slug');
            $table->unsignedInteger('tier_reached')->default(0);
            $table->timestamp('reached_at')->nullable();
            $table->unsignedInteger('progress')->default(0);
            $table->unsignedInteger('target');
            $table->timestamps();

            $table->unique(['group_id', 'milestone_slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_milestones');
    }
};
