<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolCalendarEvent extends Model
{
    use HasFactory;

    protected $table = 'school_calendar_events';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'event_date',
        'start_time',
        'end_time',
        'type',
        'is_broadcast',
        'audience',
        'created_by_admin',
    ];

    protected $casts = [
        'event_date' => 'date:Y-m-d',
        'is_broadcast' => 'boolean',
        'created_by_admin' => 'boolean',
        'user_id' => 'integer',
    ];

    const TYPES = [
        'holiday' => 'Holiday',
        'event' => 'Event',
        'reminder' => 'Reminder',
        'note' => 'Note',
    ];

    const TYPE_COLORS = [
        'holiday' => 'badge-danger',
        'event' => 'badge-primary',
        'reminder' => 'badge-warning',
        'note' => 'badge-info',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope query to return events for student:
     * - Holidays
     * - Admin broadcasted events with audience in ('all', 'students')
     * - Personal events created by the student user
     */
    public function scopeForStudent($query, $user = null)
    {
        $userId = $user ? $user->id : (auth()->check() ? auth()->id() : null);

        return $query->where(function ($q) use ($userId) {
            $q->where('type', 'holiday')
              ->orWhere(function ($adminQuery) {
                  $adminQuery->where(function ($broadcast) {
                      $broadcast->where('is_broadcast', 1)
                                ->orWhere('created_by_admin', 1);
                  })->where(function ($aud) {
                      $aud->whereIn('audience', ['all', 'students'])
                          ->orWhereNull('audience')
                          ->orWhere('audience', '');
                  });
              });

            if ($userId) {
                $q->orWhere(function ($personalQuery) use ($userId) {
                    $personalQuery->where('user_id', $userId)
                        ->where('is_broadcast', 0)
                        ->where('created_by_admin', 0);
                });
            }
        });
    }
}
