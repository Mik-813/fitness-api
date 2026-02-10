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

    public function show(WeightedProduct $weightedProduct)
    {
        return new WeightedProductResource($weightedProduct->load('product'));
    }

    public function destroy(WeightedProduct $weightedProduct)
    {
        $weightedProduct->delete();

        return response()->noContent();
    }
}