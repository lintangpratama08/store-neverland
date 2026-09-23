<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function publicIndex()
    {
        return ProductResource::collection(Product::query()->where('is_active', true)->where('stock', '>', 0)->orderBy('sort_order')->latest('id')->get());
    }

    public function index()
    {
        return ProductResource::collection(Product::query()->withCount('sales')->orderBy('sort_order')->latest('id')->get());
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $product = Product::create([...$data, 'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(5)), 'image_path' => $request->file('image')?->store('products', 'public')]);

        return (new ProductResource($product))->response()->setStatusCode(201);
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validated($request);
        $product->fill($data);
        if ($request->hasFile('image')) {
            Storage::disk('public')->delete($product->image_path);
            $product->image_path = $request->file('image')->store('products', 'public');
        }
        $product->save();

        return new ProductResource($product->fresh());
    }

    public function destroy(Product $product)
    {
        Storage::disk('public')->delete($product->image_path);
        $product->delete();

        return response()->noContent();
    }

    private function validated(Request $request, bool $requiredImage = false): array
    {
        if (is_string($request->input('variants'))) {
            $request->merge(['variants' => json_decode($request->input('variants'), true) ?: []]);
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'integer', 'min:0'],
            'stock' => ['required', 'integer', 'min:0', 'max:100000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
            'image' => [$requiredImage ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'variants' => ['nullable', 'array', 'max:20'],
            'variants.*.name' => ['required', 'string', 'max:50'],
            'variants.*.values' => ['required', 'array', 'min:1', 'max:50'],
            'variants.*.values.*' => ['required', 'string', 'max:50'],
        ]) + ['sort_order' => $request->input('sort_order', 0), 'is_active' => $request->boolean('is_active', true)];
    }
}
