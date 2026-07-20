<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtherFee extends Model
{
    use HasFactory;

    protected $table = 'other_fees';

    protected $fillable = [
        'other_fee_name',
        'other_fee_amt',
        'school_year_id',
        'current',
        'status',
    ];
}
