<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'kcal_100g',
        'carbs_100g',
        'protein_100g',
        'fat_100g',
        'sugar_100g',
        'fiber_100g',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function weightedProducts(): HasMany
    {
        return $this->hasMany(WeightedProduct::class);
    }
}