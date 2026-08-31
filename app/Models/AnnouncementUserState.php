<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnnouncementUserState extends Model
{
    use HasFactory;

    protected $table = 'announcement_user_states';

    protected $fillable = [
        'announcement_id',
        'user_id',
        'status',
    ];

    public function announcement()
    {
        return $this->belongsTo(Announcement::class, 'announcement_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
