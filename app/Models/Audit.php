<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Audit extends Model
{
    protected $fillable = [
        "user_id",
        "ip_address",
        "user_agent",
        "method",
        "data"
    ];

    public function auditable()
    {
        return $this->morphTo();
    }
}
