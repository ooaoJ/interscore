<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamStudent extends Model
{
    protected $fillable = [
        'interclass_id',
        'modality_id',
        'team_id',
        'student_id',
        'participation_type',
    ];

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

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}