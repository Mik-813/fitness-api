<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exercise extends Model
{
    use HasFactory;

    protected $fillable = [
        'record_date',
        'title',
        'muscle',
        'secondary_muscle',
        'bodypart',
        'equipment',
    ];

    public function sets()
    {
        return $this->hasMany(Set::class);
    }

    public function date()
    {
        return $this->belongsTo(Date::class, 'record_date', 'record_date');
    }
}