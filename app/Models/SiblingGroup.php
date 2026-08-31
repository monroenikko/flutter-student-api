<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiblingGroup extends Model
{
    protected $table = 'sibling_groups';

    protected $fillable = [
        'group_code',
    ];

    /**
     * Get all members of this sibling group.
     */
    public function members()
    {
        return $this->hasMany(SiblingGroupMember::class, 'sibling_group_id', 'id');
    }

    /**
     * Get all student information records in this group.
     */
    public function students()
    {
        return $this->belongsToMany(
            StudentInformation::class,
            'sibling_group_members',
            'sibling_group_id',
            'student_information_id'
        );
    }

    /**
     * Generate the next group code (SG-00001, SG-00002, ...).
     */
    public static function generateGroupCode(): string
    {
        $latest = static::orderBy('id', 'desc')->first();
        $nextNumber = $latest ? ((int) str_replace('SG-', '', $latest->group_code)) + 1 : 1;

        return 'SG-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }
}
