<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionMonthlyPayment extends Model
{
    use HasFactory;

    protected $table = 'transaction_month_paids';

    protected $fillable = [
        'or_no',
        'transaction_id',
        'student_id',
        'payment',
        'school_year_id',
        'balance',
        'online_charges',
        'email',
        'number',
        'receipt_img',
        'payment_option',
        'approval',
        'isSuccess',
        'status',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }
}
