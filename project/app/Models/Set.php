<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Set extends Model
{
    use HasFactory;

    protected $fillable = [
        'exercise_id',
        'prev_set_id',
        'rest_seconds',
        'reps_number',
        'weight_kg',
    ];

    public function exercise()
    {
        return $this->belongsTo(Exercise::class);
    }

    public function prevSet()
    {
        return $this->belongsTo(Set::class, 'prev_set_id');
    }
}