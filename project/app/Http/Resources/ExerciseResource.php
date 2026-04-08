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
            'date' => $this->record_date->format('Y-m-d'),
            'title' => $this->title,
            'muscle' => $this->muscle,
            'secondary_muscle' => $this->secondary_muscle,
            'bodypart' => $this->bodypart,
            'equipment' => $this->equipment,
            'image_url' => $this->image_url,
            'sets' => $this->whenLoaded('sets', function () use ($sortedSets) {
                return SetResource::collection($sortedSets);
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
