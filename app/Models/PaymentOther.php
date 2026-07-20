<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentOther extends Model
{
    use HasFactory;

    protected $table = 'payment_others';

    protected $fillable = [
        'payment_category_id',
        'other_fee_id',
        'status',
    ];

    public function otherFee()
    {
        return $this->belongsTo(OtherFee::class, 'other_fee_id');
    }

    public function paymentCategory()
    {
        return $this->belongsTo(PaymentCategory::class, 'payment_category_id');
    }
}
