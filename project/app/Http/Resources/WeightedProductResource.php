<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WeightedProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'weight_g' => $this->weight_g,
            $this->mergeWhen($this->relationLoaded('product'), fn () => [
                'user_id' => $this->product->user_id,
                'title' => $this->product->title,
                'kcal_100g' => $this->product->kcal_100g,
                'carbs_100g' => $this->product->carbs_100g,
                'protein_100g' => $this->product->protein_100g,
                'fat_100g' => $this->product->fat_100g,
                'sugar_100g' => $this->product->sugar_100g,
                'fiber_100g' => $this->product->fiber_100g,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
