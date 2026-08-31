<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DownpaymentFee extends Model
{
    use HasFactory;

    protected $table = "downpayment_fees";

    protected $guarded = [];
}
