<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MiscTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_route_returns_hello()
    {
        $response = $this->get('/');
        $response->assertStatus(200)
                 ->assertJson(['message' => 'Hello']);
    }

    public function test_fallback_api_route()
    {
        $response = $this->getJson('/api/non-existent');
        $response->assertStatus(404)
                 ->assertJson(['message' => 'Not Found']);
    }
}