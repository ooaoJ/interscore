<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameMatch extends Model
{
    protected $table = 'matches';

    protected $fillable = [
        'school_id',
        'interclass_id',
        'modality_id',
        'bracket_id',
        'round_id',
        'match_number',
        'team_a_id',
        'team_b_id',
        'next_match_id',
        'next_slot',
        'loser_next_match_id',
        'loser_next_slot',
        'scheduled_at',
        'location',
        'score_a',
        'score_b',
        'winner_team_id',
        'status',
        'notes',
        'registered_by',
        'approved_by',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
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

    public function bracket()
    {
        return $this->belongsTo(Bracket::class);
    }

    public function round()
    {
        return $this->belongsTo(BracketRound::class, 'round_id');
    }

    public function teamA()
    {
        return $this->belongsTo(Team::class, 'team_a_id');
    }

    public function teamB()
    {
        return $this->belongsTo(Team::class, 'team_b_id');
    }

    public function winner()
    {
        return $this->belongsTo(Team::class, 'winner_team_id');
    }

    public function nextMatch()
    {
        return $this->belongsTo(GameMatch::class, 'next_match_id');
    }

    public function loserNextMatch()
    {
        return $this->belongsTo(GameMatch::class, 'loser_next_match_id');
    }

    public function previousMatches()
    {
        return $this->hasMany(GameMatch::class, 'next_match_id');
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