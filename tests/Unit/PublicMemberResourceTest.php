<?php

namespace Tests\Unit;

use App\Http\Resources\PublicMemberResource;
use App\Models\User;
use Illuminate\Http\Request;
use Tests\TestCase;

class PublicMemberResourceTest extends TestCase
{
    public function test_public_member_payload_only_exposes_nickname_and_photo(): void
    {
        $user = new User([
            'name' => 'Nama Rahasia',
            'nickname' => 'public-player',
            'password' => 'hashed-secret',
            'role' => 'member',
        ]);

        $payload = (new PublicMemberResource($user))->toArray(Request::create('/'));

        $this->assertSame(['nickname' => 'public-player', 'photo' => null], $payload);
    }
}
