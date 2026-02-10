<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExerciseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->record_date->format('Y-m-d'),
            'title' => $this->title,
            'muscle' => $this->muscle,
            'secondary_muscle' => $this->secondary_muscle,
            'bodypart' => $this->bodypart,
            'equipment' => $this->equipment,
            'sets' => SetResource::collection($this->whenLoaded('sets')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
