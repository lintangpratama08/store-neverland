<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['visitor_key', 'last_seen_at'])]
class VisitorPresence extends Model
{
    protected function casts(): array
    {
        return ['last_seen_at' => 'datetime'];
    }
}
