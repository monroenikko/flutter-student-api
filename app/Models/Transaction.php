<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $table = 'transactions';

    protected $fillable = [
        'or_no',
        'payment_category_id',
        'student_id',
        'school_year_id',
        'downpayment_id',
        'isEnrolled',
        'status',
    ];

    public function paymentCategory()
    {
        return $this->belongsTo(PaymentCategory::class, 'payment_category_id');
    }

    public function otherFees()
    {
        return $this->hasMany(TransactionOtherFee::class, 'transaction_id');
    }

    public function monthlyPayments()
    {
        return $this->hasMany(TransactionMonthlyPayment::class, 'transaction_id');
    }
}
