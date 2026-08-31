<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GradeReleaseSchedule extends Model
{
    use HasFactory;

    protected $table = 'grade_release_schedules';

    protected $fillable = [
        'school_year_id',
        'term_type',
        'term',
        'is_enabled',
        'scheduled_at',
        'published_at',
        'published_by',
        'status',
    ];

    protected $casts = [
        'term' => 'integer',
        'is_enabled' => 'integer',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class, 'school_year_id', 'id');
    }

    public function publisher()
    {
        return $this->belongsTo(User::class, 'published_by', 'id');
    }

    public function isRevealed(): bool
    {
        return (int) $this->is_enabled === 1 && strtolower((string) $this->status) === 'published';
    }
}
