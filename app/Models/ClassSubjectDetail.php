<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassSubjectDetail extends Model
{
    use HasFactory;

    protected $table = "class_subject_details";

    public function subjectDetails()
    {
        return $this->belongsTo(SubjectDetail::class, 'subject_id', 'id');
    }

    // public function studentEnrolledSubjects()
    // {
    //     return $this->hasMany(StudentEnrolledSubject::class, 'subject_id', 'subject_id');
    // }

    public function assignFaculty()
    {
        return $this->belongsTo(FacultyInformation::class, 'faculty_id', 'id');
    }

    public function teacherSubject()
    {
        return $this->hasOne(TeacherSubject::class, 'class_subject_details_id', 'id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id', 'id');
    }

    public function classDetail()
    {
        return $this->belongsTo(ClassDetail::class, 'class_details_id', 'id');
    }

    public function subjectCategory()
    {
        return $this->belongsTo(SubjectCategory::class, 'subject_category_id', 'id');
    }
}
