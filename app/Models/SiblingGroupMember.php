<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiblingGroupMember extends Model
{
    protected $table = 'sibling_group_members';

    protected $fillable = [
        'sibling_group_id',
        'student_information_id',
    ];

    protected $casts = [
        'sibling_group_id' => 'integer',
        'student_information_id' => 'integer',
    ];

    /**
     * Get the sibling group this member belongs to.
     */
    public function siblingGroup()
    {
        return $this->belongsTo(SiblingGroup::class, 'sibling_group_id', 'id');
    }

    /**
     * Get the student information for this member.
     */
    public function student()
    {
        return $this->belongsTo(StudentInformation::class, 'student_information_id', 'id');
    }
}
