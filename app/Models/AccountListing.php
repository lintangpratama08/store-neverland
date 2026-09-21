<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'description', 'rank', 'price', 'owner_nickname', 'image_path', 'status', 'sort_order', 'is_active'])]
class AccountListing extends Model
{
    protected function casts(): array
    {
        return ['price' => 'integer', 'sort_order' => 'integer', 'is_active' => 'boolean'];
    }

    public function imageUrl(): ?string
    {
        if ($this->image_path) {
            return asset('storage/'.$this->image_path);
        }

        $cover = $this->relationLoaded('images') ? $this->images->first() : $this->images()->first();

        return $cover ? $cover->imageUrl() : null;
    }

    public function images(): HasMany
    {
        return $this->hasMany(AccountListingImage::class)->orderBy('sort_order')->orderBy('id');
    }
}
