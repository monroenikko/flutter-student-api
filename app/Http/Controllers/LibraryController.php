<?php

namespace App\Http\Controllers;

use App\Http\Requests\Library\LibraryBooksRequest;
use App\Http\Requests\Library\LibraryBorrowHistoryRequest;
use App\Services\LibraryService;

class LibraryController extends Controller
{
    public function __construct(
        protected LibraryService $service
    ) {}

    /**
     * Display student borrow history with sub-tabs and counters.
     */
    public function borrowHistory(LibraryBorrowHistoryRequest $request)
    {
        return $this->service->borrowHistory($request);
    }

    /**
     * Display single borrow record detail.
     */
    public function borrowDetail(int $id)
    {
        return $this->service->borrowDetail($id);
    }

    /**
     * Display circulation desk library books.
     */
    public function books(LibraryBooksRequest $request)
    {
        return $this->service->books($request);
    }

    /**
     * Display single library book detail.
     */
    public function bookDetail(int $id)
    {
        return $this->service->bookDetail($id);
    }

    /**
     * Display book categories for filtering.
     */
    public function categories()
    {
        return $this->service->categories();
    }
}
