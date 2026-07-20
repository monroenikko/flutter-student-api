<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionDiscount extends Model
{
    use HasFactory;

    protected $table = 'transaction_discounts';

    protected $fillable = [
        'or_no',
        'student_id',
        'discount_type',
        'category',
        'discount_amt',
        'transaction_month_paid_id',
        'school_year_id',
        'isSuccess',
    ];

    public function transactionMonthPaid()
    {
        return $this->belongsTo(TransactionMonthlyPayment::class, 'transaction_month_paid_id');
    }
}
