<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'logo_path', 'link_url', 'sort_order', 'is_active'])]
class Sponsor extends Model
{
    protected function casts(): array
    {
        return ['sort_order' => 'integer', 'is_active' => 'boolean'];
    }

    public function logoUrl(): string
    {
        return asset('storage/'.$this->logo_path);
    }
}
