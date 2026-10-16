<?php

namespace App\Services;

use App\Http\Requests\Library\LibraryBooksRequest;
use App\Http\Requests\Library\LibraryBorrowHistoryRequest;
use App\Http\Resources\Library\BookCategoryResource;
use App\Http\Resources\Library\BorrowedBookResource;
use App\Http\Resources\Library\LibraryBookResource;
use App\Models\Book;
use App\Models\BookBorrower;
use App\Models\BookCategory;
use App\Models\StudentInformation;
use App\Traits\HasSiblingAccess;
use App\Traits\ResponseApi;
use Exception;
use Illuminate\Http\Response;

class LibraryService
{
    use HasSiblingAccess, ResponseApi;

    public function __construct(
        protected Book $bookModel,
        protected BookBorrower $borrowerModel,
        protected BookCategory $categoryModel
    ) {}

    /**
     * List the student's borrow history with tabs and counters.
     */
    public function borrowHistory(LibraryBorrowHistoryRequest $request)
    {
        try {
            $student = $this->getAuthorizedStudent($request->get('student_id'));

            if (! $student) {
                return $this->error('Student information not found.', Response::HTTP_NOT_FOUND);
            }

            $baseQuery = $this->borrowerModel->newQuery()
                ->whereHas('transaction', function ($q) use ($student) {
                    $q->where('student_id', $student->id);
                });

            // Calculate counters for all tabs
            $allCount = (clone $baseQuery)->count();
            $activeCount = (clone $baseQuery)->active()->count();
            $overdueCount = (clone $baseQuery)->overdue()->count();
            $returnedCount = (clone $baseQuery)->returned()->count();

            $tab = $request->get('tab', 'all');
            $query = (clone $baseQuery)->filterTab($tab);

            $keyword = $request->get('keyword') ?? $request->get('search');
            if ($keyword) {
                $query->whereHas('book', function ($q) use ($keyword) {
                    $q->where(function ($sq) use ($keyword) {
                        $sq->where('title', 'like', '%' . $keyword . '%')
                            ->orWhere('author', 'like', '%' . $keyword . '%')
                            ->orWhere('isbn', 'like', '%' . $keyword . '%');
                    });
                });
            }

            $perPage = (int) ($request->get('per_page') ?? 10);
            $paginated = $query->with(['book.category', 'transaction.student'])
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc')
                ->paginate($perPage);

            return $this->success('Borrow history successfully listed.', Response::HTTP_OK, [
                'data' => BorrowedBookResource::collection($paginated->items()),
                'counts' => [
                    'all' => $allCount,
                    'active' => $activeCount,
                    'overdue' => $overdueCount,
                    'returned' => $returnedCount,
                ],
                'active_tab' => $tab,
                'pagination' => [
                    'current_page' => $paginated->currentPage(),
                    'last_page' => $paginated->lastPage(),
                    'per_page' => $paginated->perPage(),
                    'total' => $paginated->total(),
                    'has_more' => $paginated->hasMorePages(),
                ],
            ]);
        } catch (Exception $e) {
            return $this->error('Failed to retrieve borrow history: ' . $e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Show a single borrow record detail for the student.
     */
    public function borrowDetail(int $id)
    {
        try {
            $student = $this->getAuthorizedStudent(request('student_id'));

            if (! $student) {
                return $this->error('Student information not found.', Response::HTTP_NOT_FOUND);
            }

            $borrower = $this->borrowerModel
                ->with(['book.category', 'transaction.student'])
                ->whereHas('transaction', function ($q) use ($student) {
                    $q->where('student_id', $student->id);
                })
                ->find($id);

            if (! $borrower) {
                return $this->error('Borrow record not found.', Response::HTTP_NOT_FOUND);
            }

            return $this->success(
                'Borrow record successfully fetched.',
                Response::HTTP_OK,
                new BorrowedBookResource($borrower)
            );
        } catch (Exception $e) {
            return $this->error('Failed to retrieve borrow record: ' . $e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * List books available in the circulation desk.
     */
    public function books(LibraryBooksRequest $request)
    {
        try {
            $query = $this->bookModel->newQuery()
                ->withInventory()
                ->with('category')
                ->filter($request->validated());

            if ($request->boolean('available_only')) {
                $query->whereRaw('(qty - (SELECT COALESCE(SUM(qty), 0) FROM book_borrowers WHERE book_borrowers.book_id = books.id AND return_date IS NULL)) > 0');
            }

            $perPage = (int) ($request->get('per_page') ?? 15);
            $paginated = $query->orderBy('title', 'asc')->paginate($perPage);

            return $this->success('Library books successfully listed.', Response::HTTP_OK, [
                'data' => LibraryBookResource::collection($paginated->items()),
                'pagination' => [
                    'current_page' => $paginated->currentPage(),
                    'last_page' => $paginated->lastPage(),
                    'per_page' => $paginated->perPage(),
                    'total' => $paginated->total(),
                    'has_more' => $paginated->hasMorePages(),
                ],
            ]);
        } catch (Exception $e) {
            return $this->error('Failed to retrieve library books: ' . $e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Show single library book detail.
     */
    public function bookDetail(int $id)
    {
        try {
            $book = $this->bookModel
                ->withInventory()
                ->with('category')
                ->where('status', 1)
                ->find($id);

            if (! $book) {
                return $this->error('Book not found.', Response::HTTP_NOT_FOUND);
            }

            return $this->success(
                'Book details successfully fetched.',
                Response::HTTP_OK,
                new LibraryBookResource($book)
            );
        } catch (Exception $e) {
            return $this->error('Failed to retrieve book details: ' . $e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * List book categories for dropdown/chip filters.
     */
    public function categories()
    {
        try {
            $categories = $this->categoryModel->active()->orderBy('title', 'asc')->get();

            return $this->success(
                'Book categories successfully listed.',
                Response::HTTP_OK,
                BookCategoryResource::collection($categories)
            );
        } catch (Exception $e) {
            return $this->error('Failed to retrieve book categories: ' . $e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
