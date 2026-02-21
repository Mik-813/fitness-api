<?php

namespace App\Http\Controllers;

use App\Http\Resources\ExerciseResource;
use App\Models\Exercise;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExerciseController extends Controller
{
    public function index(Request $request)
    {
        $query = Exercise::where('user_id', $request->user()->id)
            ->with('sets');

        $date = $request->input('date');

        if ($date) {
            if ($date === 'today') {
                $date = now()->format('Y-m-d');
            }
            $query->where('record_date', $date);
        }

        return ExerciseResource::collection($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'record_date' => 'required',
            'title' => 'required|string|max:255',
            'muscle' => 'required|string|max:255',
            'secondary_muscle' => 'nullable|string|max:255',
            'bodypart' => 'required|string|max:255',
            'equipment' => 'required|string|max:255',
            'sets' => 'nullable|array',
            'sets.*.prior_rest_seconds' => 'required|integer|min:0',
            'sets.*.reps_number' => 'required|integer|min:1',
            'sets.*.weight_kg' => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($request, $validated) {
            $recordDate = Carbon::parse($validated['record_date'])->format('Y-m-d');
            
            $exercise = Exercise::create([
                'user_id' => $request->user()->id,
                'record_date' => $recordDate,
                'title' => $validated['title'],
                'muscle' => $validated['muscle'],
                'secondary_muscle' => $validated['secondary_muscle'] ?? null,
                'bodypart' => $validated['bodypart'],
                'equipment' => $validated['equipment'],
            ]);

            if (!empty($validated['sets'])) {
                $exercise->sets()->createMany($validated['sets']);
            }

            return new ExerciseResource($exercise->load('sets'));
        });
    }

    public function update(Request $request, Exercise $exercise)
    {
        $validated = $request->validate([
            'date' => 'sometimes',
            'title' => 'sometimes|string|max:255',
            'muscle' => 'sometimes|string|max:255',
            'secondary_muscle' => 'nullable|string|max:255',
            'bodypart' => 'sometimes|string|max:255',
            'equipment' => 'sometimes|string|max:255',
            'sets' => 'nullable|array',
            'sets.*.id' => 'sometimes|exists:sets,id',
            'sets.*.prior_rest_seconds' => 'required_with:sets|integer|min:0',
            'sets.*.reps_number' => 'required_with:sets|integer|min:1',
            'sets.*.weight_kg' => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($request, $exercise, $validated) {
            if (isset($validated['date'])) {
                $recordDate = Carbon::parse($validated['date'])->format('Y-m-d');
                $exercise->record_date = $recordDate;
            }

            $exercise->update($validated);

            if (isset($validated['sets'])) {
                // Simple strategy: delete existing and recreate, or update if ID present
                // For simplicity given the prompt, we'll just delete and recreate if sets are provided
                // to ensure the array matches exactly what is sent.
                $exercise->sets()->delete();
                $exercise->sets()->createMany($validated['sets']);
            }

            return new ExerciseResource($exercise->load('sets'));
        });
    }

    public function destroy(Exercise $exercise)
    {
        $exercise->delete();
        return response()->noContent();
    }
}
