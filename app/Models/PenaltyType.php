<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenaltyType extends Model
{
    protected $fillable = [
        'school_id',
        'name',
        'description',
        'default_points',
        'severity',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function penalties()
    {
        return $this->hasMany(Penalty::class);
    }
}