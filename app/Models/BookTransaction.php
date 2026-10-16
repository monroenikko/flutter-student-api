<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookTransaction extends Model
{
    use HasFactory;

    protected $table = 'book_transactions';

    protected $fillable = [
        'student_id',
        'total_qty',
        'status',
        'notes',
    ];

    protected $casts = [
        'id' => 'integer',
        'student_id' => 'integer',
        'total_qty' => 'integer',
        'status' => 'integer',
    ];

    public function student()
    {
        return $this->belongsTo(StudentInformation::class, 'student_id', 'id');
    }

    public function borrowers()
    {
        return $this->hasMany(BookBorrower::class, 'book_transactions_id', 'id');
    }
}
