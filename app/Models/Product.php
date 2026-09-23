<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'description', 'price', 'stock', 'image_path', 'variants', 'sort_order', 'is_active'])]
class Product extends Model
{
    protected function casts(): array
    {
        return ['price' => 'integer', 'stock' => 'integer', 'variants' => 'array', 'sort_order' => 'integer', 'is_active' => 'boolean'];
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? asset('storage/'.$this->image_path) : null;
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'source_id')->where('source_type', 'product');
    }
}
