<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    protected $fillable = [
        'name',
        'code',
        'stage',
        'sort_order',
    ];

    public function classrooms()
    {
        return $this->hasMany(Classroom::class);
    }

    public function competitionCategories()
    {
        return $this->belongsToMany(
            CompetitionCategory::class,
            'competition_category_grades'
        );
    }
}