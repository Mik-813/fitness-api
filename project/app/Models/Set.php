<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Set extends Model
{
    use HasFactory;

    protected $fillable = [
        'exercise_id',
        'prior_rest_seconds',
        'reps_number',
    ];

    public function exercise()
    {
        return $this->belongsTo(Exercise::class);
    }
}