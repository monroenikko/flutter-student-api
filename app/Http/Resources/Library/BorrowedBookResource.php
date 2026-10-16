<?php

namespace App\Http\Resources\Library;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BorrowedBookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $book = $this->book;
        $transaction = $this->transaction;
        $student = $transaction?->student;

        $baseUrl = config('app.sjai_url', env('SJAI_URL', 'https://sja-bataan.edu.ph'));
        $imageUrl = $book?->image ? rtrim($baseUrl, '/') . '/library/' . $book->image : null;

        $borrowedAt = $this->created_at ? Carbon::parse($this->created_at) : null;
        $dueDate = $this->due_date ? Carbon::parse($this->due_date) : null;
        $returnDate = $this->return_date ? Carbon::parse($this->return_date) : null;

        return [
            'id' => $this->id,
            'transaction_id' => $this->book_transactions_id,
            'book_id' => $this->book_id,
            'student_id' => $transaction?->student_id,
            'student_name' => $student?->full_name,
            'title' => $book?->title,
            'author' => $book?->author,
            'category' => $book?->category?->title ?? 'General',
            'category_id' => $book?->category_id,
            'isbn' => $book?->isbn,
            'qty' => (int) $this->qty,
            'image' => $book?->image,
            'image_url' => $imageUrl,
            'borrowed_date' => $borrowedAt?->format('Y-m-d'),
            'borrowed_date_formatted' => $borrowedAt?->format('M d, Y'),
            'due_date' => $dueDate?->format('Y-m-d'),
            'due_date_formatted' => $dueDate?->format('M d, Y'),
            'return_date' => $returnDate?->format('Y-m-d H:i:s'),
            'return_date_formatted' => $returnDate?->format('M d, Y'),
            'is_overdue' => (bool) $this->is_overdue,
            'is_due_today' => (bool) $this->is_due_today,
            'days_overdue' => (int) $this->days_overdue,
            'status' => $this->status_label,
            'status_color' => $this->status_color,
            'notes' => $this->notes,
        ];
    }
}
