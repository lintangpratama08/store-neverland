<?php

namespace App\Http\Controllers;

use App\Http\Resources\AccountListingResource;
use App\Models\AccountListingImage;
use App\Models\AccountListing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AccountListingController extends Controller
{
    public function publicIndex()
    {
        return AccountListingResource::collection(
            AccountListing::query()->with('images')->where('is_active', true)->where('status', 'available')->orderBy('sort_order')->latest('id')->get()
        );
    }

    public function index()
    {
        return AccountListingResource::collection(AccountListing::query()->with('images')->orderBy('sort_order')->latest('id')->get());
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $images = $this->uploadedImages($request);
        unset($data['image'], $data['images']);
        $listing = AccountListing::create([
            ...$data,
            'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(5)),
            'image_path' => null,
        ]);
        $this->storeImages($listing, $images);

        return (new AccountListingResource($listing->load('images')))->response()->setStatusCode(201);
    }

    public function update(Request $request, AccountListing $accountListing)
    {
        $data = $this->validated($request);
        $images = $this->uploadedImages($request);
        unset($data['image'], $data['images']);
        $accountListing->fill($data);
        $accountListing->save();
        $this->storeImages($accountListing, $images);

        return new AccountListingResource($accountListing->fresh()->load('images'));
    }

    public function destroy(AccountListing $accountListing)
    {
        Storage::disk('public')->delete($accountListing->image_path);
        foreach ($accountListing->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }
        $accountListing->delete();

        return response()->noContent();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'rank' => ['nullable', 'string', 'max:80'],
            'price' => ['nullable', 'integer', 'min:0'],
            'owner_nickname' => ['nullable', 'string', 'max:80'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'images' => ['nullable', 'array', 'max:20'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'status' => ['nullable', 'in:available,sold,hidden'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
        ]) + [
            'status' => $request->input('status', 'available'),
            'sort_order' => $request->input('sort_order', 0),
            'is_active' => $request->boolean('is_active', true),
        ];
    }

    private function uploadedImages(Request $request): array
    {
        $images = $request->file('images', []);
        $images = is_array($images) ? $images : [$images];
        if ($request->hasFile('image')) {
            $images[] = $request->file('image');
        }

        return array_values(array_filter($images));
    }

    private function storeImages(AccountListing $listing, array $images): void
    {
        $offset = (int) $listing->images()->max('sort_order') + 1;
        foreach ($images as $index => $image) {
            $listing->images()->create([
                'image_path' => $image->store('account-listings', 'public'),
                'sort_order' => $offset + $index,
            ]);
        }
    }

    public function destroyImage(AccountListingImage $image)
    {
        Storage::disk('public')->delete($image->image_path);
        $image->delete();

        return response()->noContent();
    }
}
