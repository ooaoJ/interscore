<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected $fillable = [
        'school_id',
        'interclass_id',
        'classroom_id',
        'modality_id',
        'name',
        'status',
        'created_by',
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

    public function modality()
    {
        return $this->belongsTo(Modality::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function teamStudents()
    {
        return $this->hasMany(TeamStudent::class);
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'team_students')
            ->withPivot('participation_type', 'interclass_id', 'modality_id')
            ->withTimestamps();
    }

    public function matchesAsTeamA()
    {
        return $this->hasMany(GameMatch::class, 'team_a_id');
    }

    public function matchesAsTeamB()
    {
        return $this->hasMany(GameMatch::class, 'team_b_id');
    }

    public function wonMatches()
    {
        return $this->hasMany(GameMatch::class, 'winner_team_id');
    }

    public function placements()
    {
        return $this->hasMany(Placement::class);
    }

    public function penalties()
    {
        return $this->hasMany(Penalty::class);
    }
}