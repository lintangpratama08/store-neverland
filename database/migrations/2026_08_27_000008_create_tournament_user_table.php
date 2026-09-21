<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_user', function (Blueprint $table): void {
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('seed')->nullable();
            $table->string('status', 16)->default('registered');
            $table->timestamp('registered_at')->nullable();
            $table->timestamps();
            $table->primary(['tournament_id', 'user_id']);
        });

        if (Schema::hasTable('tournament_team')) {
            DB::table('tournament_team')
                ->join('team_user', 'team_user.team_id', '=', 'tournament_team.team_id')
                ->select('tournament_team.tournament_id', 'team_user.user_id', 'tournament_team.registered_at', 'tournament_team.created_at', 'tournament_team.updated_at')
                ->distinct()
                ->orderBy('tournament_team.tournament_id')
                ->get()
                ->each(fn ($row) => DB::table('tournament_user')->insertOrIgnore([
                    'tournament_id' => $row->tournament_id,
                    'user_id' => $row->user_id,
                    'status' => 'registered',
                    'registered_at' => $row->registered_at,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_user');
    }
};
