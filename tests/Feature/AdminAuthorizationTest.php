<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminAuthorizationTest extends TestCase
{
    public function test_guest_cannot_access_admin_api(): void
    {
        $this->getJson('/api/admin/members')->assertUnauthorized();
    }

    public function test_member_cannot_access_admin_api(): void
    {
        $this->actingAs(User::factory()->make(['id' => 10, 'role' => 'member']))
            ->getJson('/api/admin/members')
            ->assertForbidden();
    }

    public function test_admin_api_routes_require_authentication_and_admin_middleware(): void
    {
        $route = Route::getRoutes()->match(Request::create('/api/admin/members', 'GET'));

        $this->assertContains('auth', $route->gatherMiddleware());
        $this->assertContains('admin', $route->gatherMiddleware());
    }

    public function test_reset_data_route_requires_authentication_and_admin_middleware(): void
    {
        $route = Route::getRoutes()->match(Request::create('/api/admin/reset-data', 'POST'));

        $this->assertContains('auth', $route->gatherMiddleware());
        $this->assertContains('admin', $route->gatherMiddleware());
    }
}
