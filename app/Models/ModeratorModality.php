<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModeratorModality extends Model
{
    protected $fillable = [
        'user_id',
        'interclass_id',
        'modality_id',
        'created_by',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function interclass()
    {
        return $this->belongsTo(Interclass::class);
    }

    public function modality()
    {
        return $this->belongsTo(Modality::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}