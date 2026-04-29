<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ProductNutritionController extends Controller
{
    public function generate(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'features' => 'nullable|array',
            'features.*' => 'string|in:kcal_100g,carbs_100g,protein_100g,fat_100g,sugar_100g,fiber_100g',
        ]);

        $title = $request->input('title');
        
        $requestedFields = $request->input('features', []);

        if (empty($requestedFields)) {
            return response()->json(['title' => $title]);
        }

        $model = 'gemini-2.5-flash-lite';
        $apiKey = config('services.gemini.key');
        if (!$apiKey) {
            return response()->json(['message' => 'AI generation is currently unavailable.'], 500);
        }

        $fieldsList = implode(', ', $requestedFields);
        
        $prompt = "You are a nutrition database. For the product '{$title}', provide estimated nutritional values per 100g. Only provide values for these fields: {$fieldsList}. Return the data as a raw JSON object with numeric values. If a value is unknown, return 0.";

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
        $response = Http::post($url, [
            'contents' => [
                ['parts' => [['text' => $prompt]]]
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
            ]
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $jsonString = $data['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
            $nutritionData = json_decode($jsonString, true) ?? [];
            
            return response()->json(array_merge(['title' => $title], $nutritionData));
        }

        return response()->json([
            'message' => 'Failed to generate nutrition data. Please try again later.',
        ], 502);
    }
}