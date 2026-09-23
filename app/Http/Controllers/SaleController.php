<?php

namespace App\Http\Controllers;

use App\Http\Resources\SaleResource;
use App\Models\AccountListing;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SaleController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return SaleResource::collection(Sale::query()->with('admin')->latest('sold_at')->latest('id')->get());
    }

    public function store(Request $request): SaleResource
    {
        $data = $request->validate([
            'source_type' => ['required', Rule::in(['account', 'product'])],
            'source_id' => ['required', 'integer', 'min:1'],
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'amount' => ['nullable', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $sale = DB::transaction(function () use ($data, $request): Sale {
            $model = $data['source_type'] === 'account'
                ? AccountListing::query()->lockForUpdate()->findOrFail($data['source_id'])
                : Product::query()->lockForUpdate()->findOrFail($data['source_id']);

            if ($data['source_type'] === 'account') {
                abort_if($model->status === 'sold', 422, 'Akun ini sudah ditandai terjual.');
                $model->update(['status' => 'sold']);
            } else {
                abort_unless($model->is_active, 422, 'Produk ini sedang tidak aktif.');
            }

            $quantity = (int) $data['quantity'];
            $amount = (int) ($data['amount'] ?? ((int) $model->price * $quantity));

            return Sale::create([
                'source_type' => $data['source_type'],
                'source_id' => $model->id,
                'source_name' => $model->name,
                'quantity' => $quantity,
                'amount' => $amount,
                'note' => $data['note'] ?? null,
                'admin_id' => $request->user()->id,
                'sold_at' => now(),
            ]);
        });

        return new SaleResource($sale->load('admin'));
    }

    public function destroy(Sale $sale): Response
    {
        DB::transaction(function () use ($sale): void {
            if ($sale->source_type === 'account') {
                AccountListing::query()->whereKey($sale->source_id)->where('status', 'sold')->update(['status' => 'available']);
            }

            $sale->delete();
        });

        return response()->noContent();
    }
}
