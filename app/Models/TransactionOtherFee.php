<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionOtherFee extends Model
{
    use HasFactory;

    protected $table = 'transaction_other_fees';

    protected $fillable = [
        'transaction_id',
        'or_no',
        'student_id',
        'others_fee_id',
        'school_year_id',
        'other_name',
        'item_qty',
        'item_price',
        'isSuccess',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function otherFee()
    {
        return $this->belongsTo(OtherFee::class, 'others_fee_id');
    }
}
