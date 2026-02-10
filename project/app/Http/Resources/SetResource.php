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
            'prior_rest_seconds' => $this->prior_rest_seconds,
            'reps_number' => $this->reps_number,
            'weight_kg' => $this->weight_kg,
        ];
    }
}
