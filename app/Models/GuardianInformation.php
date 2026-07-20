<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuardianInformation extends Model
{
    protected $table = 'guardian_information';

    protected $fillable = [
        'student_information_id',
        'name',
        'occupation',
        'fb_acct',
        'number'
    ];
}
