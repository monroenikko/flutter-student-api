<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookCategory extends Model
{
    use HasFactory;

    protected $table = 'book_categories';

    protected $fillable = [
        'title',
        'status',
    ];

    protected $casts = [
        'id' => 'integer',
        'status' => 'integer',
    ];

    public function books()
    {
        return $this->hasMany(Book::class, 'category_id', 'id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
