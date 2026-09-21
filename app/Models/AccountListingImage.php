<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['account_listing_id', 'image_path', 'sort_order'])]
class AccountListingImage extends Model
{
    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    public function accountListing()
    {
        return $this->belongsTo(AccountListing::class);
    }

    public function imageUrl(): string
    {
        return asset('storage/'.$this->image_path);
    }
}
