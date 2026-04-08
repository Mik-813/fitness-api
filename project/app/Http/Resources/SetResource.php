<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'prev_set_id' => $this->prev_set_id,
            'pre_rest_seconds' => $this->prevSet ? $this->prevSet->rest_seconds : 0,
            'rest_seconds' => $this->rest_seconds,
            'reps_number' => $this->reps_number,
            'weight_kg' => $this->weight_kg,
        ];
    }
}
