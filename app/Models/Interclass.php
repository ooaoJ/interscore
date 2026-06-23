<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Interclass extends Model
{
    protected $fillable = [
        'school_id',
        'name',
        'slug',
        'year',
        'start_date',
        'end_date',
        'description',
        'regulation',
        'banner_path',
        'visibility',
        'status',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function classrooms()
    {
        return $this->hasMany(Classroom::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function modalities()
    {
        return $this->hasMany(Modality::class);
    }

    public function teams()
    {
        return $this->hasMany(Team::class);
    }

    public function brackets()
    {
        return $this->hasMany(Bracket::class);
    }

    public function matches()
    {
        return $this->hasMany(GameMatch::class);
    }

    public function placements()
    {
        return $this->hasMany(Placement::class);
    }

    public function penalties()
    {
        return $this->hasMany(Penalty::class);
    }

    public function rankingSnapshots()
    {
        return $this->hasMany(RankingSnapshot::class);
    }
}