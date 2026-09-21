<?php

namespace App\Http\Controllers;

use App\Http\Resources\AuthenticatedUserResource;
use App\Http\Resources\PublicMemberResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'phone' => ['required', 'string', 'regex:/^(?:08[0-9]{8,11}|(?:\+?62)8[0-9]{8,11})$/'],
            'nickname' => ['required', 'string', 'min:3', 'max:32', 'alpha_dash', 'unique:users,nickname'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'nickname' => $data['nickname'],
            ...isset($data['phone']) ? ['phone' => preg_replace('/^0/', '62', ltrim($data['phone'], '+'))] : [],
            'password' => $data['password'],
            'role' => 'member',
            'photo_path' => $request->file('photo')?->store('members', 'public') ?? 'image/contohmember.png',
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return (new AuthenticatedUserResource($user))->response()->setStatusCode(201);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'nickname' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt([...$credentials, 'role' => 'admin'], $request->boolean('remember'))) {
            return response()->json(['message' => 'Akses admin tidak valid.'], 422);
        }

        $request->session()->regenerate();

        return new AuthenticatedUserResource($request->user());
    }

    public function me(Request $request)
    {
        return new AuthenticatedUserResource($request->user());
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }

    public function publicMembers()
    {
        return PublicMemberResource::collection(
            User::query()->where('role', 'member')->latest()->get(['id', 'nickname', 'photo_path'])
        );
    }

    public function adminMembers()
    {
        return AuthenticatedUserResource::collection(
            User::query()->latest()->get(['id', 'name', 'nickname', 'photo_path', 'role', 'phone', 'created_at'])
        );
    }

    public function storeMember(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'phone' => ['sometimes', 'required', 'string', 'regex:/^(?:08[0-9]{8,11}|(?:\+?62)8[0-9]{8,11})$/'],
            'nickname' => ['required', 'string', 'min:3', 'max:32', 'alpha_dash', 'unique:users,nickname'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(['admin', 'member'])],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $member = User::create([
            'name' => $data['name'],
            'nickname' => $data['nickname'],
            ...isset($data['phone']) ? ['phone' => preg_replace('/^0/', '62', ltrim($data['phone'], '+'))] : [],
            'password' => $data['password'],
            'role' => $data['role'],
            'photo_path' => $request->file('photo')?->store('members', 'public') ?? 'image/contohmember.png',
        ]);

        return (new AuthenticatedUserResource($member))->response()->setStatusCode(201);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'phone' => ['sometimes', 'required', 'string', 'regex:/^(?:08[0-9]{8,11}|(?:\+?62)8[0-9]{8,11})$/'],
            'nickname' => ['required', 'string', 'min:3', 'max:32', 'alpha_dash', Rule::unique('users', 'nickname')->ignore($user)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $user->fill([
            'name' => $data['name'],
            'nickname' => $data['nickname'],
            ...isset($data['phone']) ? ['phone' => preg_replace('/^0/', '62', ltrim($data['phone'], '+'))] : [],
        ]);
        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        if ($request->hasFile('photo')) {
            if ($user->photo_path) {
                Storage::disk('public')->delete($user->photo_path);
            }
            $user->photo_path = $request->file('photo')->store('members', 'public');
        }
        $user->save();

        return new AuthenticatedUserResource($user->fresh());
    }

    public function updateMember(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'phone' => ['sometimes', 'required', 'string', 'regex:/^(?:08[0-9]{8,11}|(?:\+?62)8[0-9]{8,11})$/'],
            'nickname' => ['required', 'string', 'min:3', 'max:32', 'alpha_dash', Rule::unique('users', 'nickname')->ignore($user)],
            'role' => ['required', Rule::in(['admin', 'member'])],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        $user->fill(['name' => $data['name'], 'nickname' => $data['nickname'],
            ...isset($data['phone']) ? ['phone' => preg_replace('/^0/', '62', ltrim($data['phone'], '+'))] : [], 'role' => $data['role']]);
        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        if ($request->hasFile('photo')) {
            if ($user->photo_path) {
                Storage::disk('public')->delete($user->photo_path);
            }
            $user->photo_path = $request->file('photo')->store('members', 'public');
        }
        $user->save();

        return new AuthenticatedUserResource($user->fresh());
    }

    public function destroyMember(Request $request, User $user)
    {
        abort_if($request->user()->is($user), 422, 'Admin yang sedang login tidak dapat dihapus.');
        if ($user->photo_path) {
            Storage::disk('public')->delete($user->photo_path);
        }
        $user->delete();

        return response()->noContent();
    }
}
