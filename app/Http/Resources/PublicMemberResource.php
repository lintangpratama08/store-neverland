<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'nickname' => $this->nickname,
            'photo' => $this->photoUrl(),
        ];
    }
}
