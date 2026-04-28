<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Exercise extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'record_date',
        'db_exercise_id',
    ];

    protected $casts = [
        'record_date' => 'date:Y-m-d',
    ];

    public function setDateAttribute($value)
    {
        $this->attributes['record_date'] = Carbon::parse($value)->format('Y-m-d');
    }

    public function sets()
    {
        return $this->hasMany(Set::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}