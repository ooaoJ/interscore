<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Modality extends Model
{
    protected $fillable = [
        'school_id',
        'interclass_id',
        'sport_id',
        'competition_category_id',
        'name',
        'gender',
        'team_type',
        'min_athletes',
        'max_athletes',
        'allow_draw',
        'has_third_place',
        'bracket_type',
        'status',
    ];

    protected $casts = [
        'allow_draw' => 'boolean',
        'has_third_place' => 'boolean',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function interclass()
    {
        return $this->belongsTo(Interclass::class);
    }

    public function sport()
    {
        return $this->belongsTo(Sport::class);
    }

    public function competitionCategory()
    {
        return $this->belongsTo(CompetitionCategory::class);
    }

    public function scores()
    {
        return $this->hasMany(ModalityScore::class);
    }

    public function teams()
    {
        return $this->hasMany(Team::class);
    }

    public function moderatorModalities()
    {
        return $this->hasMany(ModeratorModality::class);
    }

    public function moderators()
    {
        return $this->belongsToMany(User::class, 'moderator_modalities')
            ->withPivot('interclass_id', 'created_by')
            ->withTimestamps();
    }

    public function bracket()
    {
        return $this->hasOne(Bracket::class);
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
}