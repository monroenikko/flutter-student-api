<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubjectDetail extends Model
{
    use HasFactory;

    protected $table = "subject_details";

    protected $fillable = [
        'subject_code',
        'subject',
        'subject_abbr',
        'subject_category_id',
        'units',
        'current',
        'status',
    ];

    public function subSubjects()
    {
        return $this->hasMany(SubSubjectDetail::class, 'subject_details_id')->where('status', 1);
    }

    public function subjectCategory()
    {
        return $this->belongsTo(SubjectCategory::class, 'subject_category_id', 'id');
    }
}
