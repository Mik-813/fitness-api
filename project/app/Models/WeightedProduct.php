<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeightedProduct extends Model
{
    protected $fillable = [
        'weight_g',
        'product_id',
    ];

    protected static function booted(): void
    {
        static::deleted(function (WeightedProduct $weightedProduct) {
            if ($weightedProduct->product && $weightedProduct->product->weightedProducts()->doesntExist()) {
                $weightedProduct->product->delete();
            }
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
