<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Classroom extends Model
{
    protected $fillable = [
        'school_id',
        'interclass_id',
        'grade_id',
        'name',
        'shift',
        'course_name',
        'status',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function interclass()
    {
        return $this->belongsTo(Interclass::class);
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function teams()
    {
        return $this->hasMany(Team::class);
    }

    public function placements()
    {
        return $this->hasMany(Placement::class);
    }

    public function penalties()
    {
        return $this->hasMany(Penalty::class);
    }

    public function rankingRows()
    {
        return $this->hasMany(RankingRow::class);
    }
}