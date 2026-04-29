<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_user_settings()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/settings');

        $response->assertStatus(200);
    }

    public function test_update_modifies_user_settings()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->putJson('/api/settings', [
            'currency_sign' => '€',
            'kcal_100g' => false,
            'language' => 'pl',
            'auto_timer' => true,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('settings', [
            'user_id' => $user->id,
            'currency_sign' => '€',
            'kcal_100g' => false,
            'language' => 'pl',
            'auto_timer' => true,
        ]);
    }
}