<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['order_number', 'product_id', 'items', 'product_name', 'quantity', 'unit_price', 'customer_name', 'whatsapp', 'note', 'status', 'admin_note'])]
class Order extends Model
{
    protected function casts(): array
    {
        return ['items' => 'array', 'quantity' => 'integer', 'unit_price' => 'integer'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function total(): int
    {
        if ($this->items) {
            return collect($this->items)->sum(fn (array $item): int => (int) ($item['total'] ?? ((int) ($item['unit_price'] ?? 0) * (int) ($item['quantity'] ?? 0))));
        }

        return $this->quantity * $this->unit_price;
    }
}
