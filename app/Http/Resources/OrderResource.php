<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'product_id' => $this->product_id,
            'items' => $this->items ?: [],
            'product_name' => $this->product_name,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'total' => $this->total(),
            'customer_name' => $this->customer_name,
            'whatsapp' => $this->whatsapp,
            'note' => $this->note,
            'status' => $this->status,
            'admin_note' => $this->admin_note,
            'created_at' => $this->created_at?->format('Y-m-d H:i'),
        ];
    }
}
