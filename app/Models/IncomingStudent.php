<?php

namespace App\Models;

use App\Traits\SchoolYear;
use Illuminate\Database\Eloquent\Model;

class IncomingStudent extends Model
{
    use SchoolYear;

    protected $table = 'incoming_students';

    protected $fillable = [
        'student_id',
        'school_year_id',
        'grade_level_id',
        'student_type',
        'approval'
    ];

    public function student()
    {
        return $this->hasOne(StudentInformation::class, 'id', 'student_id');
    }

    public function studentEmail()
    {
        return $this->hasOne(StudentInformation::class, 'id', 'student_id');
    }
}
