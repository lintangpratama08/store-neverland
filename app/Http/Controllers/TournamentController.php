<?php

namespace App\Http\Controllers;

use App\Http\Resources\TournamentResource;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TournamentController extends Controller
{
    public function index()
    {
        return TournamentResource::collection(
            Tournament::withCount('participants')->whereIn('status', ['open', 'closed', 'completed'])->latest('starts_at')->get()
        );
    }

    public function adminIndex()
    {
        return TournamentResource::collection(Tournament::with(['participants:id,name,nickname,photo_path'])->withCount('participants')->latest('starts_at')->get());
    }

    public function participantContact(Request $request, Tournament $tournament, string $nickname): JsonResponse
    {
        abort_unless($request->user()->isAdmin() || ($tournament->status !== 'draft' && $tournament->participants()->whereKey($request->user()->id)->exists()), 403, 'Kontak hanya tersedia untuk peserta turnamen dan admin.');
        $member = $tournament->participants()->where('nickname', $nickname)->firstOrFail();
        abort_unless($member->phone, 422, 'Peserta belum menambahkan nomor WhatsApp.');

        return response()->json(['nickname' => $member->nickname, 'whatsapp_url' => 'https://wa.me/'.$member->phone]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:5000'],
            'game' => ['required', Rule::in(['Total Football'])],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'format' => ['required', Rule::in(['group', 'knockout', 'group_knockout'])],
            'max_teams' => ['required', 'integer', 'min:2', 'max:128'],
            'prize_pool' => ['required', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['draft', 'open', 'closed', 'completed'])],
            'is_external' => ['nullable', 'boolean'],
            'contact_whatsapp' => ['nullable', 'string', 'min:8', 'max:32'],
        ]);
        $tournament = Tournament::create([
            ...$data,
            'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(5)),
            'bracket' => ($data['is_external'] ?? false) ? null : $this->emptyBracket($data['format']),
        ]);

        return (new TournamentResource($tournament->loadCount('participants')))->response()->setStatusCode(201);
    }

    public function update(Request $request, Tournament $tournament)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:5000'],
            'game' => ['sometimes', Rule::in(['Total Football'])],
            'starts_at' => ['sometimes', 'date'],
            'ends_at' => ['nullable', 'date'],
            'format' => ['sometimes', Rule::in(['group', 'knockout', 'group_knockout'])],
            'max_teams' => ['sometimes', 'integer', 'min:2', 'max:128'],
            'prize_pool' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', Rule::in(['draft', 'open', 'closed', 'completed'])],
            'is_external' => ['nullable', 'boolean'],
            'contact_whatsapp' => ['nullable', 'string', 'min:8', 'max:32'],
        ]);
        $tournament->update($data);
        if ($tournament->is_external) {
            $tournament->update(['bracket' => null]);
        }

        return new TournamentResource($tournament->loadCount('participants'));
    }

    public function register(Request $request, Tournament $tournament)
    {
        abort_unless($tournament->status === 'open', 422, 'Turnamen belum dibuka untuk pendaftaran.');
        abort_unless($request->user()->role === 'member', 403, 'Hanya member yang dapat mendaftar.');
        $member = $request->user();
        abort_if($tournament->participants()->whereKey($member->id)->exists(), 422, 'Member sudah terdaftar di turnamen ini.');
        abort_if($tournament->registeredCount() >= $tournament->max_teams, 422, 'Slot turnamen sudah penuh.');

        DB::transaction(function () use ($tournament, $member): void {
            $tournament->participants()->attach($member->id, ['registered_at' => now()]);
            if (! $tournament->is_external) {
                $tournament->update(['bracket' => $this->buildBracket($tournament->fresh())]);
            }
        });

        $resource = new TournamentResource($tournament->fresh()->loadCount('participants'));
        if ($tournament->is_external) {
            $resource->additional(['whatsapp_url' => $this->whatsappUrl($tournament, $member->nickname)]);
        }

        return $resource;
    }

    public function randomize(Tournament $tournament)
    {
        abort_if($tournament->is_external, 422, 'Turnamen eksternal tidak memiliki bagan.');
        if (! $tournament->is_external) {
            $tournament->update(['bracket' => $this->buildBracket($tournament->fresh(), true)]);
        }

        return new TournamentResource($tournament->fresh()->loadCount('participants'));
    }

    public function updateBracket(Request $request, Tournament $tournament)
    {
        abort_if($tournament->is_external, 422, 'Turnamen eksternal tidak memiliki bagan.');
        $data = $request->validate([
            'bracket' => ['required', 'array'],
            'bracket.format' => ['required', Rule::in(['group', 'knockout', 'group_knockout'])],
            'bracket.rounds' => ['nullable', 'array'],
            'bracket.rounds.*.name' => ['required', 'string', 'max:50'],
            'bracket.rounds.*.matches' => ['required', 'array'],
            'bracket.rounds.*.matches.*.home' => ['required', 'string', 'max:80'],
            'bracket.rounds.*.matches.*.away' => ['required', 'string', 'max:80'],
            'bracket.rounds.*.matches.*.home_score' => ['nullable', 'integer', 'min:0', 'max:99'],
            'bracket.rounds.*.matches.*.away_score' => ['nullable', 'integer', 'min:0', 'max:99'],
            'bracket.rounds.*.matches.*.winner' => ['nullable', Rule::in(['home', 'away'])],
        ]);

        $bracket = $this->normalizeBracket($data['bracket']);
        $tournament->update(['bracket' => $bracket]);

        return new TournamentResource($tournament->fresh()->loadCount('participants'));
    }

    public function addParticipant(Request $request, Tournament $tournament)
    {
        $data = $request->validate(['user_id' => ['required', 'integer', 'exists:users,id']]);
        abort_if($tournament->participants()->whereKey($data['user_id'])->exists(), 422, 'Member sudah terdaftar di turnamen ini.');
        abort_if($tournament->registeredCount() >= $tournament->max_teams, 422, 'Slot turnamen sudah penuh.');
        abort_unless(User::query()->whereKey($data['user_id'])->where('role', 'member')->exists(), 422, 'Peserta harus berstatus member.');

        $tournament->participants()->attach($data['user_id'], ['registered_at' => now()]);
        $tournament->update(['bracket' => $this->buildBracket($tournament->fresh(), true)]);

        return new TournamentResource($tournament->fresh()->load(['participants:id,name,nickname,photo_path'])->loadCount('participants'));
    }

    public function removeParticipant(Tournament $tournament, User $user)
    {
        abort_unless($tournament->participants()->whereKey($user->id)->exists(), 404, 'Member tidak terdaftar di turnamen ini.');
        $tournament->participants()->detach($user->id);
        if (! $tournament->is_external) {
            $tournament->update(['bracket' => $this->buildBracket($tournament->fresh(), false)]);
        }

        return new TournamentResource($tournament->fresh()->load(['participants:id,name,nickname,photo_path'])->loadCount('participants'));
    }

    public function destroy(Tournament $tournament)
    {
        $tournament->participants()->detach();
        $tournament->delete();

        return response()->noContent();
    }

    private function emptyBracket(string $format): array
    {
        return ['format' => $format, 'rounds' => []];
    }

    private function buildBracket(Tournament $tournament, bool $randomize = false): array
    {
        $teamNames = $tournament->participants()->pluck('nickname')->all();
        if ($randomize) {
            shuffle($teamNames);
        }

        $groups = [];
        if (in_array($tournament->format, ['group', 'group_knockout'], true)) {
            $groupNames = ['A', 'B', 'C', 'D'];
            $groupCount = $tournament->format === 'group' ? 4 : 2;
            foreach ($teamNames as $index => $teamName) {
                $group = $groupNames[$index % $groupCount];
                $groups[$group][] = $teamName;
            }
        }

        return [
            'format' => $tournament->format,
            'generated_at' => now()->toISOString(),
            'teams' => $teamNames,
            'groups' => $groups,
            'rounds' => $this->knockoutRounds($teamNames, $tournament->format),
        ];
    }

    private function knockoutRounds(array $teamNames, string $format): array
    {
        if (count($teamNames) < 2 || $format === 'group') {
            return [];
        }
        $slots = $teamNames;
        $size = 1;
        while ($size < count($slots)) {
            $size *= 2;
        }
        $slots = array_pad($slots, $size, 'BYE');
        $rounds = [];
        while (count($slots) > 1) {
            $matches = [];
            foreach (array_chunk($slots, 2) as $pair) {
                $matches[] = ['home' => $pair[0], 'away' => $pair[1], 'home_score' => null, 'away_score' => null, 'status' => 'pending'];
            }
            $rounds[] = ['name' => $size === 2 ? 'final' : ($size === 4 ? 'semifinals' : 'round_of_'.$size), 'matches' => $matches];
            $slots = array_fill(0, count($matches), 'TBD');
            $size = intdiv($size, 2);
        }

        return $rounds;
    }

    private function normalizeBracket(array $bracket): array
    {
        $rounds = is_array($bracket['rounds'] ?? null) ? $bracket['rounds'] : [];
        foreach ($rounds as $roundIndex => &$round) {
            foreach ($round['matches'] as $matchIndex => &$match) {
                $homeScore = $match['home_score'] ?? null;
                $awayScore = $match['away_score'] ?? null;
                $winner = $match['winner'] ?? null;
                if ($winner === null && $homeScore !== null && $awayScore !== null && $homeScore !== $awayScore) {
                    $winner = $homeScore > $awayScore ? 'home' : 'away';
                }
                $match['home_score'] = $homeScore === null ? null : (int) $homeScore;
                $match['away_score'] = $awayScore === null ? null : (int) $awayScore;
                $match['winner'] = $winner;
                $match['status'] = $winner ? 'completed' : 'pending';
            }
            unset($match);

            if (isset($rounds[$roundIndex + 1])) {
                $winners = collect($round['matches'])->map(function (array $match): string {
                    return $match['winner'] ? $match[$match['winner']] : 'TBD';
                })->values()->all();
                foreach ($rounds[$roundIndex + 1]['matches'] as $nextIndex => &$nextMatch) {
                    $nextMatch['home'] = $winners[$nextIndex * 2] ?? 'TBD';
                    $nextMatch['away'] = $winners[$nextIndex * 2 + 1] ?? 'TBD';
                    if ($nextMatch['home'] === 'TBD' || $nextMatch['away'] === 'TBD') {
                        $nextMatch['home_score'] = null;
                        $nextMatch['away_score'] = null;
                        $nextMatch['winner'] = null;
                        $nextMatch['status'] = 'pending';
                    }
                }
                unset($nextMatch);
            }
        }
        unset($round);

        return [...$bracket, 'rounds' => $rounds, 'updated_at' => now()->toISOString()];
    }

    private function whatsappUrl(Tournament $tournament, string $nickname): string
    {
        $number = preg_replace('/\D+/', '', (string) ($tournament->contact_whatsapp ?: config('services.whatsapp.number')));
        $message = "Halo Neverland, saya {$nickname} ingin mendaftar turnamen {$tournament->name}.";

        return 'https://wa.me/'.$number.'?text='.rawurlencode($message);
    }
}
