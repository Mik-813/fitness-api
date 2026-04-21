<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Consumable;
use App\Models\Product;
use App\Models\WeightedProduct;
use App\Models\Exercise;
use App\Models\Set;

class UserSeeder extends Seeder
{
  public function run(): void
  {
    $user = User::where('email', 'test@example.com')->first();

    if (!$user) {
      $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => 'password',
      ]);
    }

    $token = $user->tokens()->firstOrCreate(
      ['name' => 'dev-token'],
      [
        'token' => hash('sha256', 'hardcoded-token-secret'),
        'abilities' => ['*'],
      ]
    );

    if (isset($this->command)) {
      $this->command->info("Test User Token: {$token->id}|hardcoded-token-secret");
    }

    $products = [
      [
        'title' => 'Chicken Breast',
        'kcal_100g' => 165,
        'carbs_100g' => 0,
        'protein_100g' => 31,
        'fat_100g' => 3.6,
        'sugar_100g' => 0,
        'fiber_100g' => 0,
      ],
      [
        'title' => 'White Rice',
        'kcal_100g' => 130,
        'carbs_100g' => 28,
        'protein_100g' => 2.7,
        'fat_100g' => 0.3,
        'sugar_100g' => 0.1,
        'fiber_100g' => 0.4,
      ],
      [
        'title' => 'Broccoli',
        'kcal_100g' => 34,
        'carbs_100g' => 6.6,
        'protein_100g' => 2.8,
        'fat_100g' => 0.4,
        'sugar_100g' => 1.7,
        'fiber_100g' => 2.6,
      ],
    ];

    foreach ($products as $index => $data) {
      $product = Product::firstOrCreate(
        ['title' => $data['title'], 'user_id' => $user->id],
        $data
      );

      $weightedProduct = WeightedProduct::firstOrCreate([
        'product_id' => $product->id,
        'weight_g' => 150,
      ]);

      $record_date = now()->subDays($index)->format('Y-m-d');

      Consumable::create([
        'weighted_product_id' => $weightedProduct->id,
        'record_date' => $record_date,
        'consumption_g' => 100,
      ]);

      $exercise = Exercise::create([
        'user_id' => $user->id,
        'record_date' => $record_date,
        'title' => 'Bench Press',
        'muscle' => 'Chest',
        'secondary_muscle' => 'Triceps',
        'bodypart' => 'Upper Body',
        'equipment' => 'Barbell',
        'image_url' => 'https://picsum.photos/200',
      ]);

      $set2 = Set::create([
        'exercise_id' => $exercise->id,
        'rest_seconds' => 0,
        'reps_number' => 6,
        'weight_kg' => 75,
      ]);

      $set1 = Set::create([
        'exercise_id' => $exercise->id,
        'prev_set_id' => $set2->id,
        'rest_seconds' => 12,
        'reps_number' => 14,
        'weight_kg' => 30,
      ]);

      Set::create([
        'exercise_id' => $exercise->id,
        'prev_set_id' => $set1->id,
        'rest_seconds' => 90,
        'reps_number' => 10,
        'weight_kg' => 55,
      ]);
    }
  }
}