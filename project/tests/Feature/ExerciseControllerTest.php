<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Exercise;

class ExerciseControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_exercise_with_sets_maintaining_order()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/exercises', [
            'record_date' => '2023-10-10',
            'db_exercise_id' => 'ztAa1RK',
            'sets' => [
                ['rest_seconds' => 60, 'reps_number' => 10, 'weight_kg' => 100],
                ['rest_seconds' => 90, 'reps_number' => 8, 'weight_kg' => 110],
                ['rest_seconds' => 120, 'reps_number' => 5, 'weight_kg' => 120],
            ]
        ]);

        $response->assertStatus(201);
        $data = $response->json();

        $this->assertCount(3, $data['sets']);
        
        $this->assertNull($data['sets'][0]['prev_set_id']);
        $this->assertEquals($data['sets'][0]['id'], $data['sets'][1]['prev_set_id']);
        $this->assertEquals($data['sets'][1]['id'], $data['sets'][2]['prev_set_id']);
        
        $this->assertEquals(0, $data['sets'][0]['pre_rest_seconds']);
        $this->assertEquals(60, $data['sets'][1]['pre_rest_seconds']);
        $this->assertEquals(90, $data['sets'][2]['pre_rest_seconds']);
    }

    public function test_can_update_exercise_and_reorder_sets()
    {
        $user = User::factory()->create();
        $exercise = Exercise::create([
            'user_id' => $user->id,
            'record_date' => '2023-10-10',
            'db_exercise_id' => 'ztAa1RK',
        ]);

        $set1 = $exercise->sets()->create(['rest_seconds' => 60, 'reps_number' => 10, 'weight_kg' => 100]);
        $set2 = $exercise->sets()->create(['prev_set_id' => $set1->id, 'rest_seconds' => 90, 'reps_number' => 8, 'weight_kg' => 110]);
        $set3 = $exercise->sets()->create(['prev_set_id' => $set2->id, 'rest_seconds' => 120, 'reps_number' => 5, 'weight_kg' => 120]);

        $response = $this->actingAs($user)->putJson("/api/exercises/{$exercise->id}", [
            'sets' => [
                ['id' => $set3->id, 'rest_seconds' => 120, 'reps_number' => 5, 'weight_kg' => 120],
                ['id' => $set1->id, 'rest_seconds' => 60, 'reps_number' => 10, 'weight_kg' => 100],
                ['rest_seconds' => 150, 'reps_number' => 3, 'weight_kg' => 130], // New set attached to the end
            ]
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertCount(3, $data['sets']);
        
        // Assert the returned sorting aligns with the newly submitted order
        $this->assertEquals($set3->id, $data['sets'][0]['id']);
        $this->assertEquals($set1->id, $data['sets'][1]['id']);
        
        $this->assertNull($data['sets'][0]['prev_set_id']);
        $this->assertEquals($set3->id, $data['sets'][1]['prev_set_id']);
        $this->assertEquals($set1->id, $data['sets'][2]['prev_set_id']);
        
        $this->assertDatabaseMissing('sets', ['id' => $set2->id]);
    }

    public function test_ignores_non_existent_set_id_on_update()
    {
        $user = User::factory()->create();
        $exercise = Exercise::create([
            'user_id' => $user->id,
            'record_date' => '2023-10-10',
            'db_exercise_id' => 'ztAa1RK',
        ]);

        $response = $this->actingAs($user)->putJson("/api/exercises/{$exercise->id}", [
            'sets' => [
                ['id' => 99999, 'rest_seconds' => 60, 'reps_number' => 10, 'weight_kg' => 100],
            ]
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertCount(1, $data['sets']);
        $this->assertNotEquals(99999, $data['sets'][0]['id']);
    }
}