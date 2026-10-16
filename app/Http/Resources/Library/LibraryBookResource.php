<?php

namespace App\Http\Resources\Library;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LibraryBookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $baseUrl = config('app.sjai_url', env('SJAI_URL', 'https://sja-bataan.edu.ph'));
        $imageUrl = $this->image ? rtrim($baseUrl, '/') . '/library/' . $this->image : null;

        $gradeLevel = $this->extracted_grade_level;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->author,
            'category' => $this->category?->title ?? 'General',
            'category_id' => $this->category_id,
            'description' => $this->remarks ?: null,
            'remarks' => $this->remarks,
            'grade_level' => $gradeLevel,
            'is_grade_applicable' => ! is_null($gradeLevel),
            'publisher' => $this->pub ?: ($this->publisher_name ?: null),
            'isbn' => $this->isbn,
            'copyright_year' => $this->copyright_year,
            'book_barcode' => $this->book_barcode,
            'image' => $this->image,
            'image_url' => $imageUrl,
            'total_stock' => (int) ($this->qty ?? 0),
            'borrowed_stock' => (int) $this->borrowed_qty,
            'available_stock' => (int) $this->available_qty,
            'is_available' => (bool) $this->is_available,
            'stock_status' => $this->stock_status,
            'stock_status_color' => $this->stock_status_color,
        ];
    }
}
