<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use HasFactory;

    protected $table="enrollments";

    public function student()
    {
        return $this->hasOne(StudentInformation::class, 'id', 'student_information_id');
    }

    public function classDetail()
    {
        return $this->belongsTo(ClassDetail::class, 'class_details_id', 'id');
    }

    public function studentEnrolledSubjects()
    {
        return $this->hasMany(StudentEnrolledSubject::class, 'enrollments_id', 'id');
    }
}