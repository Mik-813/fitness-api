<?php

namespace Tests\Feature;

use App\Models\Consumable;
use App\Models\Exercise;
use App\Models\Product;
use App\Models\User;
use App\Models\WeightedProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_dates_with_consumables_and_exercises()
    {
        $user = User::factory()->create();
        
        $product = Product::create([
            'user_id' => $user->id,
            'title' => 'Apple',
        ]);
        $wp = WeightedProduct::create(['product_id' => $product->id, 'weight_g' => 100]);
        Consumable::create([
            'weighted_product_id' => $wp->id,
            'record_date' => '2023-10-10',
            'consumption_g' => 100
        ]);

        Exercise::create([
            'user_id' => $user->id,
            'record_date' => '2023-10-12',
            'db_exercise_id' => 'abc'
        ]);
        
        Exercise::create([
            'user_id' => $user->id,
            'record_date' => '2023-10-10',
            'db_exercise_id' => 'def'
        ]);

        $response = $this->actingAs($user)->getJson('/api/dates?month=2023-10');

        $response->assertStatus(200)
                 ->assertExactJson([
                     '2023-10-10' => ['consumables', 'exercises'],
                     '2023-10-12' => ['exercises'],
                 ]);
    }

    public function test_destroy_deletes_consumables_and_exercises_for_date()
    {
        $user = User::factory()->create();
        
        $product = Product::create([
            'user_id' => $user->id,
            'title' => 'Apple',
        ]);
        $wp = WeightedProduct::create(['product_id' => $product->id, 'weight_g' => 100]);
        $consumable = Consumable::create([
            'weighted_product_id' => $wp->id,
            'record_date' => '2023-10-10',
            'consumption_g' => 100
        ]);

        $exercise = Exercise::create([
            'user_id' => $user->id,
            'record_date' => '2023-10-10',
            'db_exercise_id' => 'abc'
        ]);

        $response = $this->actingAs($user)->deleteJson('/api/dates', [
            'record_date' => '2023-10-10'
        ]);

        $response->assertStatus(204);

        $this->assertDatabaseMissing('consumables', ['id' => $consumable->id]);
        $this->assertDatabaseMissing('exercises', ['id' => $exercise->id]);
    }
}