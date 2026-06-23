<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompetitionCategory extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'sort_order',
    ];

    public function grades()
    {
        return $this->belongsToMany(
            Grade::class,
            'competition_category_grades'
        );
    }

    public function modalities()
    {
        return $this->hasMany(Modality::class);
    }
}