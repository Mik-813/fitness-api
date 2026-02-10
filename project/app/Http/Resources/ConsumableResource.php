<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsumableResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return array_merge(
            (new WeightedProductResource($this->weightedProduct))->resolve(),
            [
                'id' => $this->id,
                'record_date' => $this->record_date->format('Y-m-d'),
                'consumption_g' => $this->consumption_g,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ]
        );
    }
}
