<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Consumable extends Model
{
    protected $fillable = [
        'weighted_product_id',
        'record_date',
        'consumption_g',
    ];

    protected $casts = [
        'record_date' => 'date',
    ];

    public function weightedProduct(): BelongsTo
    {
        return $this->belongsTo(WeightedProduct::class);
    }

    public function date(): BelongsTo
    {
        return $this->belongsTo(Date::class, 'record_date', 'record_date');
    }
}
