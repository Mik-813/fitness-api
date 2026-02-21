<?php

namespace App\Http\Controllers;

use App\Http\Resources\ConsumableResource;
use App\Models\Consumable;
use App\Models\Product;
use App\Models\WeightedProduct;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConsumableController extends Controller
{
    public function index(Request $request)
    {
        $query = Consumable::whereHas('weightedProduct.product', fn($q) => $q->where('user_id', $request->user()->id))
            ->with(['weightedProduct.product']);

        $date = $request->input('record_date');

        if ($date) {
            if ($date === 'today') {
                $date = now()->format('Y-m-d');
            }
            $query->where('record_date', $date);
        }

        return ConsumableResource::collection($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'record_date' => 'required|date',
            'weight_g' => 'required|integer|min:1',
            'consumption_g' => 'required|integer|min:1|lte:weight_g',
            'title' => 'required|string|max:255',
            'kcal_100g' => 'nullable|numeric|min:0',
            'carbs_100g' => 'nullable|numeric|min:0',
            'protein_100g' => 'nullable|numeric|min:0',
            'fat_100g' => 'nullable|numeric|min:0',
            'sugar_100g' => 'nullable|numeric|min:0',
            'fiber_100g' => 'nullable|numeric|min:0',
        ]);

        $user = $request->user();
        $recordDate = Carbon::parse($validated['record_date'])->format('Y-m-d');

        return DB::transaction(function () use ($validated, $user, $recordDate) {
            $productData = array_filter($validated, fn($key) => in_array($key, [
                'kcal_100g', 'carbs_100g', 'protein_100g', 'fat_100g', 'sugar_100g', 'fiber_100g'
            ]), ARRAY_FILTER_USE_KEY);

            $product = Product::updateOrCreate(
                ['title' => $validated['title'], 'user_id' => $user->id],
                $productData
            );

            $weightedProduct = WeightedProduct::firstOrCreate([
                'product_id' => $product->id,
                'weight_g' => $validated['weight_g']
            ]);

            $exists = Consumable::where('weighted_product_id', $weightedProduct->id)
                ->where('record_date', $recordDate)
                ->exists();

            if ($exists) {
                return response()->json(['message' => 'Consumable already exists for this date.'], 422);
            }

            $consumable = Consumable::create([
                'weighted_product_id' => $weightedProduct->id,
                'record_date' => $recordDate,
                'consumption_g' => $validated['consumption_g'],
            ]);

            return new ConsumableResource($consumable->load('weightedProduct.product'));
        });
    }

    public function show(Consumable $consumable)
    {
        return new ConsumableResource($consumable->load('weightedProduct.product'));
    }

    public function update(Request $request, Consumable $consumable)
    {
        $validated = $request->validate([
            'record_date' => 'sometimes|date',
            'consumption_g' => 'sometimes|integer|min:1',
            'weight_g' => 'sometimes|integer|min:1',
            'title' => 'sometimes|string|max:255',
            'kcal_100g' => 'nullable|numeric|min:0',
            'carbs_100g' => 'nullable|numeric|min:0',
            'protein_100g' => 'nullable|numeric|min:0',
            'fat_100g' => 'nullable|numeric|min:0',
            'sugar_100g' => 'nullable|numeric|min:0',
            'fiber_100g' => 'nullable|numeric|min:0',
            'force_recreate' => 'sometimes|boolean',
        ]);

        $targetWeight = $validated['weight_g'] ?? $consumable->weightedProduct->weight_g;
        $targetConsumption = $validated['consumption_g'] ?? $consumable->consumption_g;

        if ($targetConsumption > $targetWeight) {
            throw ValidationException::withMessages(['consumption_g' => 'The consumption_g cannot be higher than weight_g.']);
        }

        return DB::transaction(function () use ($request, $consumable, $validated) {
            $user = $request->user();

            if (isset($validated['record_date'])) {
                $recordDate = Carbon::parse($validated['record_date'])->format('Y-m-d');
                $consumable->record_date = $recordDate;
            }

            if (isset($validated['consumption_g'])) {
                $consumable->consumption_g = $validated['consumption_g'];
            }

            $weightedProduct = $consumable->weightedProduct;
            $product = $weightedProduct->product;

            if (isset($validated['title']) && $validated['title'] !== $product->title) {
                $existingProduct = Product::where('user_id', $user->id)
                    ->where('title', $validated['title'])
                    ->first();

                if ($existingProduct) {
                    if (empty($validated['force_recreate'])) {
                        return response()->json([
                            'message' => "The product with the title \"{$validated['title']}\" already exists",
                            'needs_recreate' => true
                        ], 422);
                    }

                    $nutritionKeys = ['kcal_100g', 'carbs_100g', 'protein_100g', 'fat_100g', 'sugar_100g', 'fiber_100g'];
                    $productData = array_filter($validated, fn($key) => in_array($key, $nutritionKeys), ARRAY_FILTER_USE_KEY);

                    if (!empty($productData)) {
                        $existingProduct->update($productData);
                    }

                    $weight = $validated['weight_g'] ?? $weightedProduct->weight_g;

                    $newWeightedProduct = WeightedProduct::firstOrCreate([
                        'product_id' => $existingProduct->id,
                        'weight_g' => $weight,
                    ]);

                    $exists = Consumable::where('weighted_product_id', $newWeightedProduct->id)
                        ->where('record_date', Carbon::parse($consumable->record_date)->format('Y-m-d'))
                        ->where('id', '!=', $consumable->id)
                        ->exists();

                    if ($exists) {
                        return response()->json(['message' => 'Consumable already exists for this date.'], 422);
                    }

                    $consumable->weighted_product_id = $newWeightedProduct->id;
                    $consumable->save();

                    return new ConsumableResource($consumable->load('weightedProduct.product'));
                }
            }

            $nutritionKeys = ['kcal_100g', 'carbs_100g', 'protein_100g', 'fat_100g', 'sugar_100g', 'fiber_100g'];
            $productData = array_filter($validated, fn($key) => in_array($key, $nutritionKeys), ARRAY_FILTER_USE_KEY);
            
            if (isset($validated['title'])) {
                $productData['title'] = $validated['title'];
            }

            if (!empty($productData)) {
                $product->update($productData);
            }

            if (isset($validated['weight_g'])) {
                $weightedProduct->update(['weight_g' => $validated['weight_g']]);
            }

            $exists = Consumable::where('weighted_product_id', $consumable->weighted_product_id)
                ->where('record_date', Carbon::parse($consumable->record_date)->format('Y-m-d'))
                ->where('id', '!=', $consumable->id)
                ->exists();

            if ($exists) {
                return response()->json(['message' => 'Consumable already exists for this date.'], 422);
            }

            $consumable->save();

            return new ConsumableResource($consumable->load('weightedProduct.product'));
        });
    }

    public function destroy(Consumable $consumable)
    {
        $consumable->delete();
        return response()->noContent();
    }
}
