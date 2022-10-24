<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentEnrolledSubject extends Model
{
    use HasFactory;

    protected $table="student_enrolled_subjects";

    public function subjectDetails()
    {
        return $this->belongsTo(SubjectDetail::class, 'subject_id','id');
    }

    public function classSubjectDetails()
    {
        return $this->belongsTo(ClassSubjectDetail::class, 'class_subject_details_id', 'id');
    }

}