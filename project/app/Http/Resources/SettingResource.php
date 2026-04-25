<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'price' => $this->price,
            'kcal_100g' => $this->kcal_100g,
            'carbs_100g' => $this->carbs_100g,
            'protein_100g' => $this->protein_100g,
            'fat_100g' => $this->fat_100g,
            'sugar_100g' => $this->sugar_100g,
            'fiber_100g' => $this->fiber_100g,
            'currency_sign' => $this->currency_sign,
            'language' => $this->language,
            'auto_timer' => $this->auto_timer,
            'rest_limit' => $this->rest_limit,
        ];
    }
}