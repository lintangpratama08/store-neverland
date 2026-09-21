<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'description', 'slug', 'game', 'starts_at', 'ends_at', 'format', 'max_teams', 'prize_pool', 'status', 'bracket', 'is_external', 'contact_whatsapp'])]
class Tournament extends Model
{
    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'bracket' => 'array',
            'is_external' => 'boolean',
        ];
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'tournament_team')->withPivot(['seed', 'status', 'registered_at'])->withTimestamps();
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tournament_user')->withPivot(['seed', 'status', 'registered_at'])->withTimestamps();
    }

    public function registeredCount(): int
    {
        return $this->participants()->count();
    }
}
