<?php

namespace App\Models;

use Carbon\Carbon;
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
        'record_date' => 'date:Y-m-d',
    ];

    public function weightedProduct(): BelongsTo
    {
        return $this->belongsTo(WeightedProduct::class);
    }
}
