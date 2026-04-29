<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProductNutritionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_returns_title_only_if_no_features_requested()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/products/generate-nutrition', [
            'title' => 'Apple',
        ]);

        $response->assertStatus(200)
                 ->assertExactJson(['title' => 'Apple']);
    }

    public function test_generate_returns_error_if_api_key_missing()
    {
        $user = User::factory()->create();
        config(['services.gemini.key' => null]);

        $response = $this->actingAs($user)->postJson('/api/products/generate-nutrition', [
            'title' => 'Apple',
            'features' => ['kcal_100g']
        ]);

        $response->assertStatus(500)
                 ->assertJson(['message' => 'AI generation is currently unavailable.']);
    }

    public function test_generate_fetches_nutrition_data_from_gemini()
    {
        $user = User::factory()->create();
        config(['services.gemini.key' => 'fake-api-key']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => '{"kcal_100g": 52, "carbs_100g": 14}']
                            ]
                        ]
                    ]
                ]
            ], 200),
        ]);

        $response = $this->actingAs($user)->postJson('/api/products/generate-nutrition', [
            'title' => 'Apple',
            'features' => ['kcal_100g', 'carbs_100g']
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'title' => 'Apple',
                     'kcal_100g' => 52,
                     'carbs_100g' => 14,
                 ]);
    }

    public function test_generate_handles_gemini_api_failure()
    {
        $user = User::factory()->create();
        config(['services.gemini.key' => 'fake-api-key']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(null, 500),
        ]);

        $response = $this->actingAs($user)->postJson('/api/products/generate-nutrition', [
            'title' => 'Apple',
            'features' => ['kcal_100g']
        ]);

        $response->assertStatus(502)
                 ->assertJson(['message' => 'Failed to generate nutrition data. Please try again later.']);
    }
}