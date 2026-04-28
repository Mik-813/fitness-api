<?php

namespace App\Http\Controllers;

use App\Http\Resources\ExerciseResource;
use App\Models\Exercise;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ExerciseController extends Controller
{
    public function index(Request $request)
    {
        $query = Exercise::where('user_id', $request->user()->id)
            ->with('sets.prevSet');

        $date = $request->input('date');

        if ($date) {
            if ($date === 'today') {
                $date = now()->format('Y-m-d');
            }
            $query->where('record_date', $date);
        }

        $exercises = $query->get();
        $dbExerciseIds = $exercises->pluck('db_exercise_id')->unique()->filter()->join(',');

        if ($dbExerciseIds) {
            try {
                $response = Http::get("http://localhost:8081/api/v1/exercises/by-ids", [
                    'ids' => $dbExerciseIds
                ]);

                if ($response->successful() && $response->json('success')) {
                    $externalExercises = collect($response->json('data'))->keyBy('exerciseId');

                    $exercises->each(function ($exercise) use ($externalExercises) {
                        $exercise->external_data = $externalExercises->get($exercise->db_exercise_id);
                    });
                }
            } catch (\Exception $e) {
                // Ignore exception and return local data only
            }
        }

        return ExerciseResource::collection($exercises);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'record_date' => 'required',
            'db_exercise_id' => 'required|string|max:255',
            'sets' => 'nullable|array',
            'sets.*.rest_seconds' => 'required|integer|min:0',
            'sets.*.reps_number' => 'required|integer|min:1',
            'sets.*.weight_kg' => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($request, $validated) {
            $recordDate = Carbon::parse($validated['record_date'])->format('Y-m-d');
            
            $exercise = Exercise::create([
                'user_id' => $request->user()->id,
                'record_date' => $recordDate,
                'db_exercise_id' => $validated['db_exercise_id'],
            ]);

            if (!empty($validated['sets'])) {
                $prevSetId = null;
                foreach ($validated['sets'] as $setData) {
                    $setData['prev_set_id'] = $prevSetId;
                    $set = $exercise->sets()->create($setData);
                    $prevSetId = $set->id;
                }
            }

            return new ExerciseResource($exercise->load('sets.prevSet'));
        });
    }

    public function update(Request $request, Exercise $exercise)
    {
        $validated = $request->validate([
            'record_date' => 'sometimes',
            'db_exercise_id' => 'sometimes|string|max:255',
            'sets' => 'nullable|array',
            'sets.*.id' => 'sometimes|integer',
            'sets.*.rest_seconds' => 'required_with:sets|integer|min:0',
            'sets.*.reps_number' => 'required_with:sets|integer|min:0',
            'sets.*.weight_kg' => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($request, $exercise, $validated) {
            if (isset($validated['record_date'])) {
                $recordDate = Carbon::parse($validated['record_date'])->format('Y-m-d');
                $exercise->record_date = $recordDate;
            }

            $exercise->update($validated);

            if (isset($validated['sets'])) {
                $exercise->sets()->update(['prev_set_id' => null]);
                $existingIds = collect($validated['sets'])->pluck('id')->filter();
                $exercise->sets()->whereNotIn('id', $existingIds)->delete();
                
                $prevSetId = null;
                foreach ($validated['sets'] as $setData) {
                    $setData['prev_set_id'] = $prevSetId;
                    if (isset($setData['id']) && ($set = $exercise->sets()->find($setData['id']))) {
                        $set->update($setData);
                    } else {
                        $set = $exercise->sets()->create($setData);
                    }
                    $prevSetId = $set->id;
                }
            }

            return new ExerciseResource($exercise->load('sets.prevSet'));
        });
    }

    public function destroy(Exercise $exercise)
    {
        $exercise->delete();
        return response()->noContent();
    }
}
