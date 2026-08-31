<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        "subscribable_type",
        "subscribable_id",
        "player_id",
    ];

    public function subscribable()
    {
        return $this->morphTo();
    }
}
