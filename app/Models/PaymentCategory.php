<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentCategory extends Model
{
    use HasFactory;

    protected $table = 'payment_categories';

    protected $fillable = [
        'student_category_id',
        'grade_level_id',
        'tuition_fee_id',
        'misc_fee_id',
        'other_fee_id',
        'months',
        'current',
        'status',
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'payment_category_id');
    }

    public function studentCategory()
    {
        return $this->belongsTo(StudentCategory::class, 'student_category_id');
    }

    public function tuitionFee()
    {
        return $this->belongsTo(TuitionFee::class, 'tuition_fee_id');
    }

    public function miscFee()
    {
        return $this->belongsTo(MiscFee::class, 'misc_fee_id');
    }

    public function otherFee()
    {
        return $this->belongsTo(OtherFee::class, 'other_fee_id');
    }
}
