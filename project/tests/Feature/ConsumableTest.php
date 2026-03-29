<?php

namespace Tests\Feature;

use App\Models\Consumable;
use App\Models\Product;
use App\Models\User;
use App\Models\WeightedProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsumableTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_creates_parents_if_they_do_not_exist(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/api/consumables', [
            'title' => 'New Product',
            'weight_g' => 100,
            'record_date' => '2023-01-01',
            'kcal_100g' => 100,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('products', ['title' => 'New Product', 'user_id' => $user->id]);
        $this->assertDatabaseHas('weighted_products', ['weight_g' => 100]);
        $this->assertDatabaseHas('consumables', ['consumption_g' => 0, 'record_date' => '2023-01-01']);
    }

    public function test_store_aborts_if_consumable_already_exists(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = Product::create([
            'user_id' => $user->id,
            'title' => 'Apple',
            'kcal_100g' => 52,
        ]);

        $weightedProduct = WeightedProduct::create([
            'product_id' => $product->id,
            'weight_g' => 100,
        ]);

        Consumable::create([
            'weighted_product_id' => $weightedProduct->id,
            'record_date' => '2023-01-01',
            'consumption_g' => 50,
        ]);

        $response = $this->postJson('/api/consumables', [
            'title' => 'Apple',
            'weight_g' => 100,
            'record_date' => '2023-01-01',
        ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'Consumable already exists for this date.']);
    }

    public function test_store_creates_consumable_if_valid(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = Product::create([
            'user_id' => $user->id,
            'title' => 'Banana',
            'kcal_100g' => 89,
        ]);

        $weightedProduct = WeightedProduct::create([
            'product_id' => $product->id,
            'weight_g' => 120,
        ]);

        $response = $this->postJson('/api/consumables', [
            'title' => 'Banana',
            'weight_g' => 120,
            'record_date' => '2023-01-02',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('consumables', [
            'weighted_product_id' => $weightedProduct->id,
            'consumption_g' => 0,
            'record_date' => '2023-01-02',
        ]);
    }

    public function test_update_modifies_existing_consumable(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = Product::create([
            'user_id' => $user->id,
            'title' => 'Orange',
            'kcal_100g' => 47,
        ]);

        $weightedProduct = WeightedProduct::create([
            'product_id' => $product->id,
            'weight_g' => 150,
        ]);

        $consumable = Consumable::create([
            'weighted_product_id' => $weightedProduct->id,
            'record_date' => '2023-01-03',
            'consumption_g' => 150,
        ]);

        $response = $this->putJson("/api/consumables/{$consumable->id}", [
            'consumption_g' => 200,
            'weight_g' => 200,
            'record_date' => '2023-01-04',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('consumables', [
            'id' => $consumable->id,
            'consumption_g' => 200,
            'weighted_product_id' => WeightedProduct::where('weight_g', 200)->first()->id,
            'record_date' => '2023-01-04',
        ]);
    }

    public function test_update_updates_ancestors_in_place(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = Product::create([
            'user_id' => $user->id,
            'title' => 'Old Product',
            'kcal_100g' => 100,
        ]);

        $weightedProduct = WeightedProduct::create([
            'product_id' => $product->id,
            'weight_g' => 100,
        ]);

        $consumable = Consumable::create([
            'weighted_product_id' => $weightedProduct->id,
            'record_date' => '2023-01-01',
            'consumption_g' => 50,
        ]);

        $response = $this->putJson("/api/consumables/{$consumable->id}", [
            'title' => 'New Product',
            'weight_g' => 200,
            'kcal_100g' => 150,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'title' => 'New Product',
            'kcal_100g' => 150,
        ]);

        $consumable->refresh();
        $this->assertEquals(200, $consumable->weightedProduct->weight_g);
        $this->assertEquals($product->id, $consumable->weightedProduct->product_id);
    }

    public function test_update_returns_error_if_product_title_exists_and_no_override(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $productA = Product::create([
            'user_id' => $user->id,
            'title' => 'Product A',
            'kcal_100g' => 100,
        ]);
        $wpA = WeightedProduct::create(['product_id' => $productA->id, 'weight_g' => 100]);

        $consumable = Consumable::create([
            'weighted_product_id' => $wpA->id,
            'record_date' => '2023-01-01',
            'consumption_g' => 100,
        ]);

        Product::create([
            'user_id' => $user->id,
            'title' => 'Product B',
            'kcal_100g' => 200,
        ]);

        $response = $this->putJson("/api/consumables/{$consumable->id}", [
            'title' => 'Product B',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'The product with the title "Product B" already exists',
                'errors' => [
                    'needs_recreate' => true,
                ],
            ]);
    }

    public function test_update_rewires_consumable_if_product_title_exists_and_override_is_true(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $productA = Product::create([
            'user_id' => $user->id,
            'title' => 'Product A',
            'kcal_100g' => 100,
        ]);
        $wpA = WeightedProduct::create(['product_id' => $productA->id, 'weight_g' => 100]);

        $consumable = Consumable::create([
            'weighted_product_id' => $wpA->id,
            'record_date' => '2023-01-01',
            'consumption_g' => 100,
        ]);

        $productB = Product::create([
            'user_id' => $user->id,
            'title' => 'Product B',
            'kcal_100g' => 200,
        ]);

        $response = $this->putJson("/api/consumables/{$consumable->id}", [
            'title' => 'Product B',
            'override' => true,
            'weight_g' => 150,
        ]);

        $response->assertStatus(200);

        $consumable->refresh();
        $this->assertEquals($productB->id, $consumable->weightedProduct->product_id);
        $this->assertEquals(150, $consumable->weightedProduct->weight_g);

        $this->assertDatabaseHas('products', ['id' => $productA->id, 'title' => 'Product A']);
    }

    public function test_update_aborts_if_consumable_already_exists_with_override(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $date = '2023-01-01';

        $productA = Product::create(['user_id' => $user->id, 'title' => 'Apple', 'kcal_100g' => 50]);
        $wpA = WeightedProduct::create(['product_id' => $productA->id, 'weight_g' => 100]);
        Consumable::create(['weighted_product_id' => $wpA->id, 'record_date' => $date, 'consumption_g' => 50]);

        $productB = Product::create(['user_id' => $user->id, 'title' => 'Banana', 'kcal_100g' => 90]);
        $wpB = WeightedProduct::create(['product_id' => $productB->id, 'weight_g' => 100]);
        $consumableB = Consumable::create(['weighted_product_id' => $wpB->id, 'record_date' => $date, 'consumption_g' => 50]);

        $response = $this->putJson("/api/consumables/{$consumableB->id}", [
            'title' => 'Apple',
            'weight_g' => 100,
            'override' => true,
        ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'Consumable already exists for this date.']);
    }

    public function test_update_aborts_if_consumption_greater_than_weight(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = Product::create([
            'user_id' => $user->id,
            'title' => 'Banana',
            'kcal_100g' => 89,
        ]);

        $weightedProduct = WeightedProduct::create([
            'product_id' => $product->id,
            'weight_g' => 100,
        ]);

        $consumable = Consumable::create([
            'weighted_product_id' => $weightedProduct->id,
            'record_date' => '2023-01-02',
            'consumption_g' => 50,
        ]);

        $response = $this->putJson("/api/consumables/{$consumable->id}", [
            'consumption_g' => 101,
        ]);

        $response->assertStatus(422);
    }
}