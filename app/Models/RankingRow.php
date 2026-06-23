<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RankingRow extends Model
{
    protected $fillable = [
        'ranking_snapshot_id',
        'classroom_id',
        'position',
        'modality_points',
        'penalty_points',
        'total_points',
        'first_places',
        'second_places',
        'third_places',
    ];

    public function snapshot()
    {
        return $this->belongsTo(RankingSnapshot::class, 'ranking_snapshot_id');
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }
}