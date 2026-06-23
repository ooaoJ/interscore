<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'school_id',
        'name',
        'email',
        'password',
        'role',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function createdInterclasses()
    {
        return $this->hasMany(Interclass::class, 'created_by');
    }

    public function createdTeams()
    {
        return $this->hasMany(Team::class, 'created_by');
    }

    public function moderatorModalities()
    {
        return $this->hasMany(ModeratorModality::class);
    }

    public function generatedBrackets()
    {
        return $this->hasMany(Bracket::class, 'generated_by');
    }

    public function registeredMatches()
    {
        return $this->hasMany(GameMatch::class, 'registered_by');
    }

    public function approvedMatches()
    {
        return $this->hasMany(GameMatch::class, 'approved_by');
    }

    public function registeredPenalties()
    {
        return $this->hasMany(Penalty::class, 'registered_by');
    }

    public function approvedPenalties()
    {
        return $this->hasMany(Penalty::class, 'approved_by');
    }

    public function isPlatformAdmin(): bool
    {
        return $this->role === 'platform_admin';
    }

    public function isSchoolManager(): bool
    {
        return $this->role === 'school_manager';
    }

    public function isModerator(): bool
    {
        return $this->role === 'moderator';
    }
}