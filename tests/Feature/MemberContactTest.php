<?php

namespace Tests\Feature;

use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_requires_a_valid_phone_and_normalizes_it(): void
    {
        $data = ['name' => 'Adit', 'nickname' => 'adit', 'password' => 'password123', 'password_confirmation' => 'password123'];
        $this->postJson('/api/auth/register', $data)->assertUnprocessable()->assertJsonValidationErrors('phone');
        $this->postJson('/api/auth/register', [...$data, 'phone' => 'not-a-phone'])->assertUnprocessable()->assertJsonValidationErrors('phone');
        $this->postJson('/api/auth/register', [...$data, 'phone' => '085158311928', 'role' => 'admin'])->assertCreated()->assertJsonPath('data.phone', '6285158311928')->assertJsonPath('data.role', 'member');
        $this->assertDatabaseHas('users', ['nickname' => 'adit', 'phone' => '6285158311928']);
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/auth/me')->assertOk()->assertJsonPath('data.nickname', 'adit');
        $this->postJson('/api/auth/logout')->assertNoContent();
        $this->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_profile_updates_phone_without_exposing_it_publicly(): void
    {
        $member = User::factory()->create(['phone' => null]);
        $this->actingAs($member)->postJson('/api/profile', ['name' => $member->name, 'nickname' => $member->nickname, 'phone' => '+6285158311928'])->assertOk()->assertJsonPath('data.phone', '6285158311928');
        $this->getJson('/api/public/members')->assertOk()->assertJsonMissingPath('data.0.phone');
        $this->postJson('/api/profile', ['name' => $member->name, 'nickname' => $member->nickname, 'phone' => '123'])->assertUnprocessable()->assertJsonValidationErrors('phone');
        $this->assertSame('6285158311928', $member->fresh()->phone);
    }

    public function test_only_participants_and_admin_can_get_tournament_contact(): void
    {
        $tournament = Tournament::create(['name' => 'Open', 'slug' => 'open', 'game' => 'Total Football', 'starts_at' => '2026-09-09', 'format' => 'knockout', 'status' => 'open']);
        $adit = User::factory()->create(['nickname' => 'adit', 'phone' => '6285158311928']);
        $jarwo = User::factory()->create(['nickname' => 'jarwo']);
        $outsider = User::factory()->create();
        $tournament->participants()->attach([$adit->id, $jarwo->id]);
        $url = '/api/tournaments/'.$tournament->id.'/contact/';
        $this->getJson($url.'adit')->assertUnauthorized();
        $this->actingAs($outsider)->getJson($url.'adit')->assertForbidden();
        $this->actingAs($jarwo)->getJson($url.'adit')->assertOk()->assertJsonPath('whatsapp_url', 'https://wa.me/6285158311928');
        $this->getJson($url.$outsider->nickname)->assertNotFound();
        $this->getJson($url.'jarwo')->assertUnprocessable();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->getJson($url.'adit')->assertOk();
        $this->getJson('/api/public/tournaments')->assertOk()->assertJsonMissingPath('data.0.phone')->assertJsonMissingPath('data.0.participants');
    }
}
