<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookBorrower extends Model
{
    use HasFactory;

    protected $table = 'book_borrowers';

    protected $fillable = [
        'book_transactions_id',
        'book_id',
        'qty',
        'due_date',
        'return_date',
        'created_by',
        'modified_by',
        'notes',
    ];

    protected $casts = [
        'id' => 'integer',
        'book_transactions_id' => 'integer',
        'book_id' => 'integer',
        'qty' => 'integer',
        'due_date' => 'date',
        'return_date' => 'datetime',
        'created_by' => 'integer',
        'modified_by' => 'integer',
    ];

    public function transaction()
    {
        return $this->belongsTo(BookTransaction::class, 'book_transactions_id', 'id');
    }

    public function book()
    {
        return $this->belongsTo(Book::class, 'book_id', 'id');
    }

    public function getIsOverdueAttribute(): bool
    {
        if ($this->return_date !== null || ! $this->due_date) {
            return false;
        }

        return Carbon::parse($this->due_date)->startOfDay()->lt(Carbon::today());
    }

    public function getIsDueTodayAttribute(): bool
    {
        if ($this->return_date !== null || ! $this->due_date) {
            return false;
        }

        return Carbon::parse($this->due_date)->startOfDay()->equalTo(Carbon::today());
    }

    public function getDaysOverdueAttribute(): int
    {
        if (! $this->is_overdue || ! $this->due_date) {
            return 0;
        }

        return (int) Carbon::parse($this->due_date)->startOfDay()->diffInDays(Carbon::today());
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->return_date !== null) {
            return 'Returned';
        }

        if ($this->is_overdue) {
            return 'Overdue';
        }

        return 'Active';
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status_label) {
            'Returned' => 'badge-success',
            'Overdue' => 'badge-danger',
            default => 'badge-warning',
        };
    }

    public function scopeActive($query)
    {
        return $query->whereNull('return_date')
            ->where(function ($q) {
                $q->whereNull('due_date')
                    ->orWhereDate('due_date', '>=', Carbon::today());
            });
    }

    public function scopeOverdue($query)
    {
        return $query->whereNull('return_date')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', Carbon::today());
    }

    public function scopeReturned($query)
    {
        return $query->whereNotNull('return_date');
    }

    public function scopeFilterTab($query, ?string $tab)
    {
        return match (strtolower((string) $tab)) {
            'active' => $query->active(),
            'overdue' => $query->overdue(),
            'returned', 'history' => $query->returned(),
            default => $query,
        };
    }
}
