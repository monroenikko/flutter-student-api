<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntranceExamSchedule extends Model
{
    protected $table = 'entrance_exam_schedules';

    protected $fillable = [
        'student_information_id',
        'date_time',
        'status'
    ];
}
