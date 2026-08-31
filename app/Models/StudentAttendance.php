<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentAttendance extends Model
{
    use HasFactory;

    protected $table="student_attendances";

    protected $fillable = [
        'school_year_id',
        'junior_months_header',
        'senior1_months_header',
        'senior2_months_header',
        'senior3_months_header',
    ];
}