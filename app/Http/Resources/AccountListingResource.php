<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountListingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'rank' => $this->rank,
            'price' => $this->price,
            'owner_nickname' => $this->owner_nickname,
            'image_url' => $this->imageUrl(),
            'images' => AccountListingImageResource::collection($this->whenLoaded('images')),
            'status' => $this->status,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'sold_count' => (int) ($this->sold_count ?? 0),
        ];
    }
}
