<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModalityScore extends Model
{
    protected $fillable = [
        'modality_id',
        'position',
        'points',
    ];

    public function modality()
    {
        return $this->belongsTo(Modality::class);
    }
}