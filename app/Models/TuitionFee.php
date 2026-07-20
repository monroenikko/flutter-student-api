<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TuitionFee extends Model
{
    use HasFactory;

    protected $table = 'tuition_fees';

    protected $fillable = [
        'tuition_amt',
        'current',
        'status',
    ];
}
