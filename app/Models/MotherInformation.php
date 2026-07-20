<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MotherInformation extends Model
{
    protected $table = 'mother_information';

    protected $fillable = [
        'student_information_id',
        'name',
        'occupation',
        'fb_acct',
        'number'
    ];
}
