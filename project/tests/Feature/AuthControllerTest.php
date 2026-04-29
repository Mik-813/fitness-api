<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register()
    {
        Http::fake([
            '*' => Http::response(['success' => true, 'score' => 0.9], 200),
        ]);

        $response = $this->postJson('/api/auth/register', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'recaptcha_token' => 'dummy_token',
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure(['user', 'token']);
                 
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_user_cannot_register_with_invalid_data()
    {
        $response = $this->postJson('/api/auth/register', [
            'email' => 'not-an-email',
            'password' => 'short',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email', 'password', 'recaptcha_token']);
    }

    public function test_user_can_login()
    {
        Http::fake([
            '*' => Http::response(['success' => true, 'score' => 0.9], 200),
        ]);

        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
            'recaptcha_token' => 'dummy_token',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['user', 'token']);
    }

    public function test_user_cannot_login_with_invalid_credentials()
    {
        Http::fake([
            '*' => Http::response(['success' => true, 'score' => 0.9], 200),
        ]);

        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrongpassword',
            'recaptcha_token' => 'dummy_token',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    public function test_user_can_logout()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Logged out']);
    }

    public function test_user_can_send_verification_email()
    {
        Mail::fake();

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/auth/email/send-verification', [
            'url' => 'http://localhost/verify',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Verification email sent']);

        $this->assertDatabaseHas('email_verification_tokens', [
            'email' => $user->email,
        ]);
    }

    public function test_user_can_verify_email()
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        
        DB::table('email_verification_tokens')->insert([
            'email' => $user->email,
            'token' => 'valid-token',
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/verify', [
            'token' => 'valid-token',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Email verified successfully']);

        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertDatabaseMissing('email_verification_tokens', [
            'email' => $user->email,
        ]);
    }

    public function test_user_cannot_verify_with_invalid_token()
    {
        $response = $this->postJson('/api/auth/verify', [
            'token' => 'invalid-token',
        ]);

        $response->assertStatus(422)
                 ->assertJson(['message' => 'Invalid or expired token']);
    }

    public function test_user_can_send_reset_password_email()
    {
        Mail::fake();

        $user = User::factory()->create();

        $response = $this->postJson('/api/auth/email/send-reset-password', [
            'email' => $user->email,
            'url' => 'http://localhost/reset-password',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Password reset email sent']);

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }

    public function test_user_can_reset_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword'),
        ]);

        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => 'reset-token',
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/reset-password', [
            'token' => 'reset-token',
            'password' => 'newpassword',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Password reset successfully']);

        $this->assertTrue(Hash::check('newpassword', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }
}