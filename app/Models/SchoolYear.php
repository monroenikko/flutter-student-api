<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolYear extends Model
{
    use HasFactory;

    protected $table="school_years";

    public function scopeFilter($query)
    {
        return $query->where('status', 1);
    }

}
