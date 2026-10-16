<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;

    protected $table = 'books';

    protected $fillable = [
        'title',
        'category_id',
        'author',
        'author_2',
        'author_3',
        'author_4',
        'author_5',
        'qty',
        'pub',
        'publisher_name',
        'isbn',
        'copyright_year',
        'book_status',
        'book_barcode',
        'image',
        'date_added',
        'remarks',
        'status',
    ];

    protected $casts = [
        'id' => 'integer',
        'category_id' => 'integer',
        'qty' => 'integer',
        'copyright_year' => 'integer',
        'status' => 'integer',
        'date_added' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(BookCategory::class, 'category_id', 'id');
    }

    public function borrowers()
    {
        return $this->hasMany(BookBorrower::class, 'book_id', 'id');
    }

    public function activeBorrowers()
    {
        return $this->hasMany(BookBorrower::class, 'book_id', 'id')->whereNull('return_date');
    }

    public function getBorrowedQtyAttribute(): int
    {
        if (array_key_exists('borrowed_qty', $this->attributes)) {
            return (int) $this->attributes['borrowed_qty'];
        }

        return (int) $this->activeBorrowers()->sum('qty');
    }

    public function getAvailableQtyAttribute(): int
    {
        $borrowed = $this->borrowed_qty;
        $total = (int) ($this->qty ?? 0);

        return max(0, $total - $borrowed);
    }

    public function getIsAvailableAttribute(): bool
    {
        return $this->available_qty > 0;
    }

    public function getStockStatusAttribute(): string
    {
        if ($this->available_qty <= 0) {
            return 'Out of Stock';
        }

        if ($this->available_qty <= 2) {
            return 'Low Stock';
        }

        return 'In Stock';
    }

    public function getStockStatusColorAttribute(): string
    {
        return match ($this->stock_status) {
            'Out of Stock' => 'badge-danger',
            'Low Stock' => 'badge-warning',
            default => 'badge-success',
        };
    }

    public function getExtractedGradeLevelAttribute(): ?string
    {
        $title = $this->title ?? '';
        $remarks = $this->remarks ?? '';
        $categoryTitle = $this->category?->title ?? '';

        // 1. Check title for "Grade X" or "Gr. X"
        if (preg_match('/\b(?:Grade|Gr\.?)\s*(\d{1,2})\b/i', $title, $matches)) {
            return 'Grade ' . $matches[1];
        }

        // 2. Check title for word numbers (e.g. Grade Seven)
        $wordMap = [
            'one' => 1, 'two' => 2, 'three' => 3, 'four' => 4, 'five' => 5,
            'six' => 6, 'seven' => 7, 'eight' => 8, 'nine' => 9, 'ten' => 10,
            'eleven' => 11, 'twelve' => 12,
        ];
        if (preg_match('/\bGrade\s*(one|two|three|four|five|six|seven|eight|nine|ten|eleven|twelve)\b/i', $title, $matches)) {
            $num = $wordMap[strtolower($matches[1])] ?? null;
            if ($num) {
                return 'Grade ' . $num;
            }
        }

        // 3. Check for SHS category or title
        if (strcasecmp($categoryTitle, 'SHS') === 0 || preg_match('/\bSHS\b/i', $title)) {
            return 'Senior High School (SHS)';
        }

        // 4. Check remarks for "Grade X"
        if (preg_match('/\b(?:Grade|Gr\.?)\s*(\d{1,2})\b/i', $remarks, $matches)) {
            return 'Grade ' . $matches[1];
        }

        return null;
    }

    public function scopeWithInventory($query)
    {
        return $query->withSum(['borrowers as borrowed_qty' => function ($q) {
            $q->whereNull('return_date');
        }], 'qty');
    }

    public function scopeFilter($query, $params = [])
    {
        $keyword = is_array($params) ? ($params['keyword'] ?? $params['search'] ?? null) : ($params->keyword ?? $params->search ?? null);
        $categoryId = is_array($params) ? ($params['category_id'] ?? null) : ($params->category_id ?? null);
        $gradeLevel = is_array($params) ? ($params['grade_level'] ?? null) : ($params->grade_level ?? null);

        return $query->where('status', 1)
            ->when($keyword, function ($q) use ($keyword) {
                $q->where(function ($sq) use ($keyword) {
                    $sq->where('title', 'like', '%' . $keyword . '%')
                        ->orWhere('author', 'like', '%' . $keyword . '%')
                        ->orWhere('isbn', 'like', '%' . $keyword . '%')
                        ->orWhere('pub', 'like', '%' . $keyword . '%')
                        ->orWhere('publisher_name', 'like', '%' . $keyword . '%')
                        ->orWhere('book_barcode', 'like', '%' . $keyword . '%')
                        ->orWhere('remarks', 'like', '%' . $keyword . '%');
                });
            })
            ->when($categoryId, function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            })
            ->when($gradeLevel, function ($q) use ($gradeLevel) {
                $q->where(function ($sq) use ($gradeLevel) {
                    $sq->where('title', 'like', '%' . $gradeLevel . '%')
                        ->orWhere('remarks', 'like', '%' . $gradeLevel . '%');
                });
            });
    }
}
