<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournaments', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('game');
            $table->date('starts_at');
            $table->date('ends_at')->nullable();
            $table->string('format', 24)->default('knockout');
            $table->unsignedSmallInteger('max_teams')->default(16);
            $table->unsignedBigInteger('prize_pool')->default(0);
            $table->string('status', 16)->default('draft');
            $table->json('bracket')->nullable();
            $table->timestamps();
        });

        Schema::create('tournament_team', function (Blueprint $table): void {
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('seed')->nullable();
            $table->string('status', 16)->default('registered');
            $table->timestamp('registered_at')->nullable();
            $table->timestamps();
            $table->primary(['tournament_id', 'team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_team');
        Schema::dropIfExists('tournaments');
    }
};
