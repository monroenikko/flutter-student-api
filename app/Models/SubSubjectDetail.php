<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubSubjectDetail extends Model
{
    use HasFactory;

    protected $table = 'sub_subject_details';

    protected $fillable = [
        'subject_details_id',
        'sub_subject_code',
        'sub_subject',
        'units',
        'status',
        'current',
    ];

    public function parentSubject()
    {
        return $this->belongsTo(SubjectDetail::class, 'subject_details_id');
    }
}
