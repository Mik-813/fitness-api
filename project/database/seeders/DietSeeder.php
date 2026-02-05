<?php

namespace Database\Seeders;

use App\Models\Consumable;
use App\Models\Date;
use App\Models\Product;
use App\Models\User;
use App\Models\WeightedProduct;
use Illuminate\Database\Seeder;

class DietSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first() ?? User::factory()->create();

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
            $product = Product::create(array_merge($data, ['user_id' => $user->id]));

            $weightedProduct = WeightedProduct::create([
                'product_id' => $product->id,
                'weight_g' => 100,
            ]);

            $record_date = now()->subDays($index)->format('Y-m-d');

            $date = Date::firstOrCreate(
                ['record_date' => $record_date],
                ['user_id' => $user->id]
             );

            Consumable::create([
                'weighted_product_id' => $weightedProduct->id,
                'record_date' => $date->record_date,
                'consumption_g' => 150,
            ]);
        }
    }
}