<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;

    const AUDIENCE = [
        1 => 'Junior High',
        2 => 'Senior High',
        3 => 'All Students',
        4 => 'All Users',
    ];

    const AUDIENCE_DESIGN = [
        1 => 'badge-primary',
        2 => 'badge-success',
        3 => 'badge-info',
        4 => 'badge-secondary',
    ];

    const STATUS = [
        1 => 'Published',
        2 => 'Draft',
        3 => 'Archived',
    ];

    const STATUS_DESIGN = [
        1 => 'badge-success',
        2 => 'badge-warning',
        3 => 'badge-secondary',
    ];

    const RECIPIENT_STATUS = [
        1 => 'Unread',
        2 => 'Read',
        3 => 'Archived',
    ];

    const RECIPIENT_STATUS_DESIGN = [
        1 => 'badge-danger',
        2 => 'badge-success',
        3 => 'badge-secondary',
    ];

    protected $table = 'announcements';

    protected $fillable = [
        'title',
        'content',
        'audience',
        'status',
        'user_id',
        'published_at',
        'slug',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function userStates()
    {
        return $this->hasMany(AnnouncementUserState::class, 'announcement_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function scopeFilter($query, $params)
    {
        return $query->when($params, function ($q) use ($params) {
            $table = $q->getModel()->getTable();
            $q->where(function ($search) use ($params, $table) {
                $search->where($table . '.title', 'like', '%' . $params . '%')
                    ->orWhere($table . '.content', 'like', '%' . $params . '%');
            });
        });
    }

    public function scopeStatusFilter($query, $status = null)
    {
        return $query->when($status && $status !== 'all', function ($q) use ($status) {
            $q->where($q->getModel()->getTable() . '.status', $status);
        });
    }

    public function scopePublished($query)
    {
        return $query->where($query->getModel()->getTable() . '.status', 1);
    }

    public function scopeForStudentLevel($query, $level = null)
    {
        if (! $level) {
            return $query;
        }

        return $query->whereIn($query->getModel()->getTable() . '.audience', [(int) $level, 3, 4]);
    }
}
