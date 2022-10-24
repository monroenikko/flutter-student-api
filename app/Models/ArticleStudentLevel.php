<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArticleStudentLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'article_id',
        'student_level',
        'status'
    ];
}