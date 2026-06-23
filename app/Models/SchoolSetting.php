<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolSetting extends Model
{
    protected $fillable = [
        'school_id',
        'show_student_full_name_public',
        'show_student_identifier_public',
        'allow_public_history',
        'allow_public_team_students',
    ];

    protected $casts = [
        'show_student_full_name_public' => 'boolean',
        'show_student_identifier_public' => 'boolean',
        'allow_public_history' => 'boolean',
        'allow_public_team_students' => 'boolean',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}