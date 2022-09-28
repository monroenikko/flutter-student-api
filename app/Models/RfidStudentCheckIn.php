<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RfidStudentCheckIn extends Model
{
    use HasFactory;

    protected $table="rfid_student_check_ins";
}