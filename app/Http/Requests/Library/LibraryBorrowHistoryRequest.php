<?php

namespace App\Http\Requests\Library;

use Illuminate\Foundation\Http\FormRequest;

class LibraryBorrowHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tab' => ['nullable', 'string', 'in:all,active,overdue,returned,history'],
            'keyword' => ['nullable', 'string', 'max:100'],
            'search' => ['nullable', 'string', 'max:100'],
            'student_id' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
