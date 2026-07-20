<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FatherInformation extends Model
{
    protected $table = 'father_information';

    protected $fillable = [
        'student_information_id',
        'name',
        'occupation',
        'fb_acct',
        'number'
    ];
}
