<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TournamentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'slug' => $this->slug,
            'game' => $this->game,
            'starts_at' => $this->starts_at?->format('Y-m-d'),
            'ends_at' => $this->ends_at?->format('Y-m-d'),
            'format' => $this->format,
            'max_teams' => $this->max_teams,
            'registered_members' => $this->whenCounted('participants'),
            'prize_pool' => $this->prize_pool,
            'status' => $this->status,
            'is_external' => $this->is_external,
            'contact_whatsapp' => $this->when($request->user()?->isAdmin(), $this->contact_whatsapp),
            'bracket' => $this->bracket,
            'teams' => $this->whenLoaded('teams', fn () => $this->teams->map(fn ($team) => ['id' => $team->id, 'name' => $team->name, 'code' => $team->code])->values()),
            'participants' => $this->whenLoaded('participants', fn () => $this->participants->map(fn ($member) => ['id' => $member->id, 'name' => $member->name, 'nickname' => $member->nickname, 'photo' => $member->photoUrl()])->values()),
        ];
    }
}
