<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RankingSnapshot extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'school_id',
        'interclass_id',
        'generated_by',
        'generated_at',
        'notes',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function interclass()
    {
        return $this->belongsTo(Interclass::class);
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function rows()
    {
        return $this->hasMany(RankingRow::class);
    }
}