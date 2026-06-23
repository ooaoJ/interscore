<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BracketRound extends Model
{
    protected $fillable = [
        'bracket_id',
        'round_number',
        'name',
        'sort_order',
    ];

    public function bracket()
    {
        return $this->belongsTo(Bracket::class);
    }

    public function matches()
    {
        return $this->hasMany(GameMatch::class, 'round_id');
    }
}