<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RfidInformation extends Model
{
    use HasFactory;
    protected $table="rfid_information";
    protected $fillable = ['student_information_id', 'school_year_id', 'rfid', 'photo'];
}