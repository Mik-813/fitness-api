<?php

namespace App\Http\Controllers;

use App\Http\Resources\WeightedProductResource;
use App\Models\WeightedProduct;
use App\Models\Product;
use Illuminate\Http\Request;

class WeightedProductController extends Controller
{
    public function index()
    {
        $weightedProducts = WeightedProduct::with('product')->get();

        return WeightedProductResource::collection($weightedProducts);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'weight_g' => 'required|integer|min:1',
            'title' => 'required|string|max:255',
            'kcal_100g' => 'nullable|numeric|min:0',
            'carbs_100g' => 'nullable|numeric|min:0',
            'protein_100g' => 'nullable|numeric|min:0',
            'fat_100g' => 'nullable|numeric|min:0',
            'sugar_100g' => 'nullable|numeric|min:0',
            'fiber_100g' => 'nullable|numeric|min:0',
        ]);

        $product = Product::create(array_merge($validated, ['user_id' => $request->user()->id]));

        $weightedProduct = WeightedProduct::create([
            'product_id' => $product->id,
            'weight_g' => $validated['weight_g'],
        ]);

        return new WeightedProductResource($weightedProduct->load('product'));
    }

    public function show(WeightedProduct $weightedProduct)
    {
        return new WeightedProductResource($weightedProduct->load('product'));
    }

    public function update(Request $request, WeightedProduct $weightedProduct)
    {
        $validated = $request->validate([
            'weight_g' => 'sometimes|integer|min:1',
            'title' => 'sometimes|string|max:255',
            'kcal_100g' => 'nullable|numeric|min:0',
            'carbs_100g' => 'nullable|numeric|min:0',
            'protein_100g' => 'nullable|numeric|min:0',
            'fat_100g' => 'nullable|numeric|min:0',
            'sugar_100g' => 'nullable|numeric|min:0',
            'fiber_100g' => 'nullable|numeric|min:0',
        ]);

        if (array_key_exists('weight_g', $validated)) {
            $weightedProduct->update(['weight_g' => $validated['weight_g']]);
        }
        $weightedProduct->product->update($validated);

        return new WeightedProductResource($weightedProduct->load('product'));
    }

    public function destroy(WeightedProduct $weightedProduct)
    {
        $weightedProduct->delete();

        return response()->noContent();
    }
}