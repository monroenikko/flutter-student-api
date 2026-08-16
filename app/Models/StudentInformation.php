<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentInformation extends Model
{
    use HasFactory;

    protected $table = "student_informations";

    protected $fillable = [
        'first_name', //
        'middle_name', //
        'last_name', //
        'c_address', //
        'p_address', //
        'email', //
        'contact_number', //
        'photo', //
        'user_id', //
        'current', //
        'status', //
        'religion', //
        'citizenship', //
        'fb_acct', //
        'place_of_birth', //
        'no_siblings', //
        'isEsc',
        'age',
        'gender',
    ];

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'age' => 'integer',
        'gender' => 'integer',
    ];

    public function getFullNameAttribute()
    {
        return ucwords($this->last_name . ', ' . $this->first_name . ' ' . $this->middle_name);
    }

    public function student()
    {
        return $this->belongsTo(RfidLog::class);
    }

    public function father()
    {
        return $this->hasOne(FatherInformation::class, 'student_information_id', 'id');
    }

    public function mother()
    {
        return $this->hasOne(MotherInformation::class, 'student_information_id', 'id');
    }

    public function guardian()
    {
        return $this->hasOne(GuardianInformation::class, 'student_information_id', 'id');
    }

    public function siblings()
    {
        return $this->hasMany(SiblingInformation::class, 'student_information_id', 'id');
    }

    public function examSchedule()
    {
        return $this->hasOne(EntranceExamSchedule::class, 'student_information_id', 'id');
    }

    public function studentEducation()
    {
        return $this->hasOne(StudentEducation::class, 'student_information_id', 'id');
    }

    public function incomingStudent()
    {
        return $this->hasOne(IncomingStudent::class, 'student_id', 'id');
    }

    public function hasTransaction()
    {
        $school_year = SchoolYear::where('current', 1)->where('status', 1)->first();
        $school_year_id = $school_year ? $school_year->id : null;
        return $this->hasOne(Transaction::class, 'student_id', 'id')->where('school_year_id', $school_year_id);
    }

    public static function studentInfo()
    {
        return static::with([
            'father',
            'mother',
            'guardian',
            'siblings',
            'examSchedule',
            'studentEducation',
            'incomingStudent:id,student_id,school_year_id,grade_level_id',
            'hasTransaction:id,student_id,school_year_id'
        ])
            ->where('user_id', auth()->id())->first();
    }
}
