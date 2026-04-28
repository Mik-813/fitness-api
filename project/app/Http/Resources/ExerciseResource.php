<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExerciseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $sortedSets = [];
        if ($this->relationLoaded('sets')) {
            $sets = $this->sets;
            $setsById = $sets->keyBy('id');
            $current = $sets->whereNull('prev_set_id')->first();
            
            while ($current) {
                $sortedSets[] = $current;
                $current = $sets->where('prev_set_id', $current->id)->first();
            }
            
            $linkedIds = collect($sortedSets)->pluck('id');
            $unlinkedSets = $sets->whereNotIn('id', $linkedIds);
            foreach ($unlinkedSets as $unlinkedSet) {
                $sortedSets[] = $unlinkedSet;
            }
        }

        return [
            'id' => $this->id,
            'record_date' => $this->record_date->format('Y-m-d'),
            'db_exercise_id' => $this->db_exercise_id,
            'exercise' => $this->when(isset($this->external_data), $this->external_data),
            'sets' => $this->whenLoaded('sets', function () use ($sortedSets) {
                return SetResource::collection($sortedSets);
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
