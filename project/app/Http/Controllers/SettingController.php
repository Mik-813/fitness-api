<?php

namespace App\Http\Controllers;

use App\Http\Resources\SettingResource;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingController extends Controller
{
    public function index(Request $request)
    {
        return new SettingResource($request->user()->setting);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'price' => ['sometimes', 'string'],
            'kcal_100g' => ['sometimes', 'boolean'],
            'carbs_100g' => ['sometimes', 'boolean'],
            'protein_100g' => ['sometimes', 'boolean'],
            'fat_100g' => ['sometimes', 'boolean'],
            'sugar_100g' => ['sometimes', 'boolean'],
            'fiber_100g' => ['sometimes', 'boolean'],
            'currency_sign' => ['sometimes', 'nullable', 'string'],
            'language' => ['sometimes', Rule::in(['en', 'pl', 'ua'])],
            'auto_timer' => ['sometimes', 'boolean'],
            'rest_limit' => ['sometimes', 'integer', 'min:0'],
        ]);

        $setting = $request->user()->setting()->updateOrCreate([], $validated);

        return new SettingResource($setting);
    }
}