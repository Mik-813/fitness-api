<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Tests\TestCase;
use Illuminate\Support\Facades\Http;

class SocialAuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_redirect_to_google()
    {
        Http::fake([
            '*' => Http::response(['success' => true, 'score' => 0.9], 200),
        ]);

        $mockDriver = \Mockery::mock();
        $mockDriver->shouldReceive('stateless')->andReturnSelf();
        $mockDriver->shouldReceive('redirect')->andReturnSelf();
        $mockDriver->shouldReceive('getTargetUrl')->andReturn('https://accounts.google.com/o/oauth2/auth');

        Socialite::shouldReceive('driver')->with('google')->andReturn($mockDriver);

        $response = $this->postJson('/api/auth/google/redirect', [
            'recaptcha_token' => 'dummy_token'
        ]);

        $response->assertStatus(200)
                 ->assertJson(['url' => 'https://accounts.google.com/o/oauth2/auth']);
    }

    public function test_callback_creates_user_and_returns_token()
    {
        $googleUser = \Mockery::mock(\Laravel\Socialite\Two\User::class);
        $googleUser->shouldReceive('getEmail')->andReturn('test@example.com');
        $googleUser->shouldReceive('getId')->andReturn('123456789');

        $mockDriver = \Mockery::mock();
        $mockDriver->shouldReceive('stateless')->andReturnSelf();
        $mockDriver->shouldReceive('user')->andReturn($googleUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($mockDriver);

        $response = $this->getJson('/api/auth/google/callback');

        $response->assertStatus(200)
                 ->assertJsonStructure(['token']);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'google_id' => '123456789',
        ]);
    }
}