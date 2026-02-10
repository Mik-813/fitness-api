<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\WeightedProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeightedProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_title_unique_constraint_on_db_level(): void
    {
        $user = User::factory()->create();
        
        Product::create([
            'user_id' => $user->id,
            'title' => 'Unique Product',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Product::create([
            'user_id' => $user->id,
            'title' => 'Unique Product',
        ]);
    }

    public function test_index_returns_weighted_products(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = Product::create(['user_id' => $user->id, 'title' => 'Apple']);
        WeightedProduct::create(['product_id' => $product->id, 'weight_g' => 100]);

        $response = $this->getJson('/api/weighted-products');

        $response->assertStatus(200);
    }

    public function test_store_is_not_allowed(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/api/weighted-products', []);
        $response->assertStatus(405);
    }
}