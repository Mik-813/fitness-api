<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'price',
        'kcal_100g',
        'carbs_100g',
        'protein_100g',
        'fat_100g',
        'sugar_100g',
        'fiber_100g',
        'currency_sign',
        'language',
        'auto_timer',
    ];

    protected $casts = [
        'kcal_100g' => 'boolean',
        'carbs_100g' => 'boolean',
        'protein_100g' => 'boolean',
        'fat_100g' => 'boolean',
        'sugar_100g' => 'boolean',
        'fiber_100g' => 'boolean',
        'auto_timer' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function setCurrencySignAttribute(?string $value): void
    {
        $this->attributes['currency_sign'] = $value ?? '';
    }
}