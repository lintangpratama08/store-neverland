<?php

namespace App\Http\Controllers;

use App\Http\Resources\SponsorResource;
use App\Models\Sponsor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SponsorController extends Controller
{
    public function publicIndex()
    {
        return SponsorResource::collection(
            Sponsor::query()->where('is_active', true)->orderBy('sort_order')->latest('id')->get()
        );
    }

    public function index()
    {
        return SponsorResource::collection(Sponsor::query()->orderBy('sort_order')->latest('id')->get());
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, true);
        $sponsor = Sponsor::create([
            ...$data,
            'logo_path' => $request->file('logo')->store('sponsors', 'public'),
        ]);

        return (new SponsorResource($sponsor))->response()->setStatusCode(201);
    }

    public function update(Request $request, Sponsor $sponsor)
    {
        $data = $this->validated($request);
        $sponsor->fill($data);
        if ($request->hasFile('logo')) {
            Storage::disk('public')->delete($sponsor->logo_path);
            $sponsor->logo_path = $request->file('logo')->store('sponsors', 'public');
        }
        $sponsor->save();

        return new SponsorResource($sponsor->fresh());
    }

    public function destroy(Sponsor $sponsor)
    {
        Storage::disk('public')->delete($sponsor->logo_path);
        $sponsor->delete();

        return response()->noContent();
    }

    private function validated(Request $request, bool $creating = false): array
    {
        return $request->validate([
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'max:120'],
            'logo' => [$creating ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:10240'],
            'link_url' => ['nullable', 'url', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
        ]) + [
            'sort_order' => $request->input('sort_order', 0),
            'is_active' => $request->boolean('is_active', true),
        ];
    }
}
