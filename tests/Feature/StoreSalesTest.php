<?php

namespace Tests\Feature;

use App\Models\AccountListing;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreSalesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_mark_an_account_as_sold_and_create_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $listing = AccountListing::create([
            'name' => 'Elite Matchday',
            'slug' => 'elite-matchday-test',
            'price' => 450000,
            'status' => 'available',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->postJson('/api/admin/sales', [
                'source_type' => 'account',
                'source_id' => $listing->id,
                'quantity' => 1,
                'amount' => 450000,
            ])
            ->assertCreated()
            ->assertJsonPath('data.source_type', 'account');

        $this->assertDatabaseHas('account_listings', ['id' => $listing->id, 'status' => 'sold']);
        $this->assertDatabaseHas('sales', ['source_type' => 'account', 'source_id' => $listing->id, 'quantity' => 1]);
    }

    public function test_non_admin_cannot_access_store_sales_history(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'member']))
            ->getJson('/api/admin/sales')
            ->assertForbidden();
    }

    public function test_admin_can_mark_merchandise_as_sold(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::create([
            'name' => 'Neverland Jersey',
            'slug' => 'neverland-jersey-test',
            'price' => 250000,
            'stock' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->postJson('/api/admin/sales', [
                'source_type' => 'product',
                'source_id' => $product->id,
                'quantity' => 2,
                'amount' => 500000,
            ])
            ->assertCreated()
            ->assertJsonPath('data.source_type', 'product');

        $this->assertDatabaseHas('sales', ['source_type' => 'product', 'source_id' => $product->id, 'quantity' => 2, 'amount' => 500000]);
    }
}
