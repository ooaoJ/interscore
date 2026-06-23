<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'logo_path',
        'status',
    ];

    public function settings()
    {
        return $this->hasOne(SchoolSetting::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function interclasses()
    {
        return $this->hasMany(Interclass::class);
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

    public function matches()
    {
        return $this->hasMany(GameMatch::class);
    }

    public function penalties()
    {
        return $this->hasMany(Penalty::class);
    }
}