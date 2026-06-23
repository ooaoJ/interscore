<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Placement extends Model
{
    protected $fillable = [
        'school_id',
        'interclass_id',
        'modality_id',
        'team_id',
        'classroom_id',
        'position',
        'points_awarded',
        'defined_by',
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

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function definedBy()
    {
        return $this->belongsTo(User::class, 'defined_by');
    }
}