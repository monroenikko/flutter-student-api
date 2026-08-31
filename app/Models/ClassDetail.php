<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassDetail extends Model
{
    use HasFactory;

    protected $table = "class_details";

    protected $fillable = [
        'school_year_id',
        'section_id',
        'room_id',
        'adviser_id',
        'grade_level',
        'term_type',
        'strand_id',
        'current',
        'status',
    ];

    public function enrollment()
    {
        return $this->hasOne(Enrollment::class, 'class_id', 'id');
    }

    public function section()
    {
        return $this->belongsTo(SectionDetail::class, 'section_id', 'id');
    }

    public function adviser()
    {
        return $this->belongsTo(FacultyInformation::class, 'adviser_id', 'id');
    }

   public function classSubjectDetails()
    {
        return $this->hasMany(ClassSubjectDetail::class, 'class_details_id', 'id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id', 'id');
    }

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class, 'school_year_id', 'id');
    }
}
