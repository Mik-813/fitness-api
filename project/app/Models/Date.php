<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Date extends Model
{
    protected $primaryKey = 'record_date';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'record_date',
        'user_id',
    ];

    protected $casts = [
        'record_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function consumables(): HasMany
    {
        return $this->hasMany(Consumable::class, 'record_date', 'record_date');
    }
}
