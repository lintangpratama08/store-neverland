<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'source_name' => $this->source_name,
            'quantity' => $this->quantity,
            'amount' => $this->amount,
            'note' => $this->note,
            'sold_at' => $this->sold_at?->format('Y-m-d H:i'),
            'admin_name' => $this->admin?->name ?: $this->admin?->nickname,
        ];
    }
}
