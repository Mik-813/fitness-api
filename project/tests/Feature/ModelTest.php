<?php

namespace Tests\Feature;

use App\Models\Consumable;
use App\Models\Exercise;
use App\Models\Product;
use App\Models\Set;
use App\Models\Setting;
use App\Models\User;
use App\Models\WeightedProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_is_admin()
    {
        $user = User::factory()->create();
        $this->assertFalse($user->isAdmin());

        $user->role = User::ROLE_ADMIN;
        $this->assertTrue($user->isAdmin());
    }

    public function test_user_creates_setting_on_created()
    {
        $user = User::factory()->create();
        
        $this->assertDatabaseHas('settings', [
            'user_id' => $user->id,
        ]);
        
        $this->assertNotNull($user->setting);
        $this->assertEquals('$', $user->setting->currency_sign);
    }

    public function test_setting_currency_sign_mutator()
    {
        $setting = new Setting();

        $setting->currency_sign = null;
        $this->assertEquals('', $setting->currency_sign);

        $setting->currency_sign = '€';
        $this->assertEquals('€', $setting->currency_sign);
    }

    public function test_weighted_product_deletes_product_when_last_deleted()
    {
        $user = User::factory()->create();
        $product = Product::create([
            'user_id' => $user->id,
            'title' => 'Test Product',
        ]);

        $wp1 = WeightedProduct::create(['product_id' => $product->id, 'weight_g' => 100]);
        $wp2 = WeightedProduct::create(['product_id' => $product->id, 'weight_g' => 200]);

        $wp1->delete();
        $this->assertDatabaseHas('products', ['id' => $product->id]);

        $wp2->delete();
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_exercise_set_date_attribute()
    {
        $exercise = new Exercise();
        $exercise->date = '2023-10-10 12:34:56';
        
        $this->assertEquals('2023-10-10', $exercise->record_date->format('Y-m-d'));
    }

    public function test_product_relations()
    {
        $user = User::factory()->create();
        $product = Product::create([
            'user_id' => $user->id,
            'title' => 'Test Product',
        ]);

        $this->assertEquals($user->id, $product->user->id);
        
        $wp = WeightedProduct::create(['product_id' => $product->id, 'weight_g' => 100]);
        $this->assertTrue($product->weightedProducts->contains($wp));
    }

    public function test_set_relations()
    {
        $user = User::factory()->create();
        $exercise = Exercise::create([
            'user_id' => $user->id,
            'record_date' => '2023-10-10',
            'db_exercise_id' => '123'
        ]);

        $set1 = $exercise->sets()->create(['rest_seconds' => 60, 'reps_number' => 10, 'weight_kg' => 100]);
        $set2 = $exercise->sets()->create(['prev_set_id' => $set1->id, 'rest_seconds' => 90, 'reps_number' => 8, 'weight_kg' => 110]);

        $this->assertEquals($exercise->id, $set2->exercise->id);
        $this->assertEquals($set1->id, $set2->prevSet->id);
    }

    public function test_consumable_relations()
    {
        $user = User::factory()->create();
        $product = Product::create([
            'user_id' => $user->id,
            'title' => 'Test Product',
        ]);
        $wp = WeightedProduct::create(['product_id' => $product->id, 'weight_g' => 100]);
        $consumable = Consumable::create([
            'weighted_product_id' => $wp->id,
            'record_date' => '2023-10-10',
            'consumption_g' => 100
        ]);

        $this->assertEquals($wp->id, $consumable->weightedProduct->id);
    }
}