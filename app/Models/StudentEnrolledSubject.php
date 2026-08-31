<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentEnrolledSubject extends Model
{
    use HasFactory;

    protected $table = "student_enrolled_subjects";

    protected $fillable = [
        'subject_id',
        'sub_subject_id',
        'enrollments_id',
        'class_subject_details_id',
        'fir_g',
        'sec_g',
        'thi_g',
        'fou_g',
        'status',
        'sem',
    ];

    public function subjectDetails()
    {
        return $this->belongsTo(SubjectDetail::class, 'subject_id', 'id');
    }

    public function subSubject()
    {
        return $this->belongsTo(SubSubjectDetail::class, 'sub_subject_id', 'id');
    }

    public function classSubjectDetails()
    {
        return $this->belongsTo(ClassSubjectDetail::class, 'class_subject_details_id', 'id');
    }
}
