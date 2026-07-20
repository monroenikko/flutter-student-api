<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiblingInformation extends Model
{
    protected $table = 'sibling_information';

    protected $fillable = [
        'student_information_id',
        'name',
        'grade_level_id'
    ];
}
