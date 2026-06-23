<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penalty extends Model
{
    protected $fillable = [
        'school_id',
        'interclass_id',
        'modality_id',
        'match_id',
        'team_id',
        'classroom_id',
        'student_id',
        'penalty_type_id',
        'points',
        'reason',
        'status',
        'registered_by',
        'approved_by',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function interclass()
    {
        return $this->belongsTo(Interclass::class);
    }

    public function modality()
    {
        return $this->belongsTo(Modality::class);
    }

    public function match()
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function penaltyType()
    {
        return $this->belongsTo(PenaltyType::class);
    }

    public function registeredBy()
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}