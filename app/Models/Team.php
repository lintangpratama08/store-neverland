<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'slug', 'code', 'game', 'division', 'tone', 'logo_path'])]
class Team extends Model
{
    public function logoUrl(): ?string
    {
        return $this->logo_path ? asset('storage/'.$this->logo_path) : null;
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('position')->withTimestamps();
    }

    public function tournaments(): BelongsToMany
    {
        return $this->belongsToMany(Tournament::class, 'tournament_team')->withPivot(['seed', 'status', 'registered_at'])->withTimestamps();
    }
}
