<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentInformation extends Model
{
    use HasFactory;

    protected $table="student_informations";

    protected $fillable = [
        'first_name', //
        'middle_name', //
        'last_name',//
        'c_address',//
        'p_address',//
        'email',//
        'contact_number',//
        'photo',//
        'user_id',//
        'current',//
        'status',//
        'religion',//
        'citizenship',//
        'fb_acct',//
        'place_of_birth',//
        'no_siblings',//
        'isEsc',
        'age',
        'gender',
    ];

    public function getFullNameAttribute()
    {
        return ucwords($this->last_name . ', ' . $this->first_name. ' ' . $this->middle_name);
    }

    public function student()
    {
        return $this->belongsTo(RfidLog::class);
    }

}