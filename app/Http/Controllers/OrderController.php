<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index()
    {
        return OrderResource::collection(Order::query()->latest()->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['nullable', 'integer', 'exists:products,id', 'required_without:items'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99', 'required_without:items'],
            'items' => ['nullable', 'array', 'min:1', 'max:30'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.options' => ['nullable', 'array', 'max:20'],
            'items.*.options.*' => ['nullable', 'string', 'max:80'],
            'customer_name' => ['required', 'string', 'max:100'],
            'whatsapp' => ['required', 'string', 'min:8', 'max:32'],
            'admin_whatsapp' => ['nullable', 'string', 'min:8', 'max:32'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $requestedItems = $data['items'] ?? [[
            'product_id' => $data['product_id'],
            'quantity' => $data['quantity'],
            'options' => [],
        ]];
        $items = DB::transaction(function () use ($requestedItems, $data): array {
            $items = [];
            foreach ($requestedItems as $requestedItem) {
                $product = Product::query()->whereKey($requestedItem['product_id'])->where('is_active', true)->lockForUpdate()->firstOrFail();
                $quantity = (int) $requestedItem['quantity'];
                abort_if($product->stock < $quantity, 422, 'Stok produk '.$product->name.' tidak mencukupi.');
                $options = $requestedItem['options'] ?? [];
                $this->validateOptions($product, $options);
                $product->decrement('stock', $quantity);
                $items[] = ['product_id' => $product->id, 'name' => $product->name, 'quantity' => $quantity, 'unit_price' => $product->price, 'options' => $options, 'total' => $product->price * $quantity];
            }

            $order = Order::create([
                'order_number' => 'NL-'.now()->format('ymd').'-'.Str::upper(Str::random(5)),
                'product_id' => count($items) === 1 ? $items[0]['product_id'] : null,
                'items' => $items,
                'product_name' => count($items) === 1 ? $items[0]['name'] : count($items).' produk',
                'quantity' => collect($items)->sum('quantity'),
                'unit_price' => count($items) === 1 ? $items[0]['unit_price'] : 0,
                'customer_name' => $data['customer_name'],
                'whatsapp' => $data['whatsapp'],
                'note' => $data['note'] ?? null,
            ]);

            return [$order, $items];
        });
        [$order, $items] = $items;
        $allowedNumbers = collect([config('services.whatsapp.admin_one'), config('services.whatsapp.admin_two'), config('services.whatsapp.number')])->filter()->map(fn ($value): string => preg_replace('/\D+/', '', (string) $value))->filter()->values();
        $requestedNumber = preg_replace('/\D+/', '', (string) ($data['admin_whatsapp'] ?? ''));
        $number = $allowedNumbers->contains($requestedNumber) ? $requestedNumber : $allowedNumbers->first();
        $lines = collect($items)->map(fn (array $item): string => '- '.$item['name'].' x'.$item['quantity'].($item['options'] ? ' ('.collect($item['options'])->map(fn ($value, $key) => $key.': '.$value)->implode(', ').')' : ''))->implode("\n");
        $message = "Halo Neverland, saya ingin memesan ({$order->order_number})\nNama: {$order->customer_name}\nWhatsApp: {$order->whatsapp}\nPesanan:\n{$lines}\nTotal: Rp ".number_format($order->total(), 0, ',', '.')."\nCatatan: ".($order->note ?: '-');

        return (new OrderResource($order->fresh()))->additional(['whatsapp_url' => 'https://wa.me/'.$number.'?text='.rawurlencode($message)])->response()->setStatusCode(201);
    }

    public function update(Request $request, Order $order)
    {
        $data = $request->validate(['status' => ['required', Rule::in(['pending', 'confirmed', 'packed', 'completed', 'cancelled'])], 'admin_note' => ['nullable', 'string', 'max:2000']]);
        $order->update($data);

        return new OrderResource($order->fresh());
    }

    public function destroy(Order $order)
    {
        $order->delete();

        return response()->noContent();
    }

    private function validateOptions(Product $product, array $options): void
    {
        foreach ($product->variants ?: [] as $variant) {
            $name = $variant['name'] ?? null;
            if (! $name || ! array_key_exists($name, $options)) {
                continue;
            }
            $allowed = $variant['values'] ?? [];
            abort_unless(in_array($options[$name], $allowed, true), 422, 'Pilihan '.$name.' tidak tersedia.');
        }
    }
}
