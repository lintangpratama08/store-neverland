<?php

namespace App\Http\Controllers;

use App\Http\Resources\MediaAssetResource;
use App\Models\MediaAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaAssetController extends Controller
{
    public function publicIndex()
    {
        return MediaAssetResource::collection(
            MediaAsset::query()->where('is_active', true)->orderBy('sort_order')->latest('id')->get()
        );
    }

    public function index()
    {
        return MediaAssetResource::collection(MediaAsset::query()->orderBy('sort_order')->latest('id')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:60'],
            'title' => ['required', 'string', 'max:120'],
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'link_url' => ['nullable', 'url', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $asset = MediaAsset::create([
            'key' => $data['key'],
            'title' => $data['title'],
            'image_path' => $request->file('image')->store('media', 'public'),
            'link_url' => $data['link_url'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return (new MediaAssetResource($asset))->response()->setStatusCode(201);
    }

    public function update(Request $request, MediaAsset $media)
    {
        $data = $request->validate([
            'key' => ['sometimes', 'string', 'max:60'],
            'title' => ['sometimes', 'string', 'max:120'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'link_url' => ['nullable', 'url', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $media->fill([
            'key' => $data['key'] ?? $media->key,
            'title' => $data['title'] ?? $media->title,
            'link_url' => $data['link_url'] ?? null,
            'sort_order' => $data['sort_order'] ?? $media->sort_order,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $media->is_active,
        ]);
        if ($request->hasFile('image')) {
            Storage::disk('public')->delete($media->image_path);
            $media->image_path = $request->file('image')->store('media', 'public');
        }
        $media->save();

        return new MediaAssetResource($media->fresh());
    }

    public function destroy(MediaAsset $media)
    {
        Storage::disk('public')->delete($media->image_path);
        $media->delete();

        return response()->noContent();
    }
}
