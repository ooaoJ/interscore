<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bracket extends Model
{
    protected $fillable = [
        'school_id',
        'interclass_id',
        'modality_id',
        'name',
        'type',
        'status',
        'random_seed',
        'generated_by',
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

    public function generator()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function rounds()
    {
        return $this->hasMany(BracketRound::class);
    }

    public function matches()
    {
        return $this->hasMany(GameMatch::class);
    }
}