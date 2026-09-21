<?php

namespace App\Http\Controllers;

use App\Http\Resources\TeamResource;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TeamController extends Controller
{
    public function index()
    {
        return TeamResource::collection(
            Team::with('members:id,nickname,photo_path')->orderBy('name')->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'code' => ['required', 'string', 'max:8'],
            'game' => ['required', Rule::in(['Total Football'])],
            'division' => ['nullable', 'string', 'max:50'],
            'tone' => ['nullable', 'in:blue,coral,purple,ink,lime'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'member_ids' => ['array'],
            'member_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $memberIds = $data['member_ids'] ?? [];
        unset($data['member_ids'], $data['logo']);
        $team = Team::create([
            ...$data,
            'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(5)),
            'tone' => $data['tone'] ?? 'blue',
            'logo_path' => $request->file('logo')?->store('teams', 'public') ?? 'image/logo.png',
        ]);
        $team->members()->sync($memberIds);

        return new TeamResource($team->load('members:id,nickname,photo_path'));
    }

    public function update(Request $request, Team $team)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:80'],
            'code' => ['sometimes', 'string', 'max:8'],
            'game' => ['sometimes', Rule::in(['Total Football'])],
            'division' => ['nullable', 'string', 'max:50'],
            'tone' => ['nullable', 'in:blue,coral,purple,ink,lime'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'member_ids' => ['array'],
            'member_ids.*' => ['integer', 'exists:users,id'],
        ]);
        $memberIds = $data['member_ids'] ?? null;
        unset($data['member_ids'], $data['logo']);
        $team->update($data);
        if ($request->hasFile('logo')) {
            if ($team->logo_path && $team->logo_path !== 'image/logo.png') {
                Storage::disk('public')->delete($team->logo_path);
            }
            $team->logo_path = $request->file('logo')->store('teams', 'public');
            $team->save();
        }
        if ($memberIds !== null) {
            $team->members()->sync($memberIds);
        }

        return new TeamResource($team->load('members:id,nickname,photo_path'));
    }

    public function transfer(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'from_team_id' => ['required', 'integer', 'exists:teams,id'],
            'to_team_id' => ['required', 'integer', 'exists:teams,id', 'different:from_team_id'],
        ]);
        $member = User::query()->whereKey($data['user_id'])->where('role', 'member')->firstOrFail();
        $source = Team::findOrFail($data['from_team_id']);
        $target = Team::findOrFail($data['to_team_id']);
        abort_unless($source->members()->whereKey($member->id)->exists(), 422, 'Anggota tidak berada di team asal.');

        DB::transaction(function () use ($source, $target, $member): void {
            $source->members()->detach($member->id);
            $target->members()->syncWithoutDetaching([$member->id]);
        });

        return new TeamResource($target->fresh()->load('members:id,nickname,photo_path'));
    }

    public function destroy(Team $team)
    {
        if ($team->logo_path && $team->logo_path !== 'image/logo.png') {
            Storage::disk('public')->delete($team->logo_path);
        }
        $team->delete();

        return response()->noContent();
    }
}
