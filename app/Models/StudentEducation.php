<?php

namespace App\Models;

use App\Traits\SchoolYear;
use Illuminate\Database\Eloquent\Model;

class StudentEducation extends Model
{
    use SchoolYear;

    protected $table = 'student_education';

    protected $fillable = [
        'student_information_id',
        'school_name',
        'school_type',
        'school_address',
        'last_sy_attended',
        'gw_average',
        'strand',
        'is_transferee',
    ];
}
