<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = [
        'school_id',
        'interclass_id',
        'classroom_id',
        'name',
        'display_name',
        'birth_date',
        'gender',
        'school_identifier',
        'status',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function interclass()
    {
        return $this->belongsTo(Interclass::class);
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function teamStudents()
    {
        return $this->hasMany(TeamStudent::class);
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'team_students')
            ->withPivot('participation_type', 'interclass_id', 'modality_id')
            ->withTimestamps();
    }

    public function penalties()
    {
        return $this->hasMany(Penalty::class);
    }
}