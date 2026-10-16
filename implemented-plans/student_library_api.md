# Implementation Plan: Student Library API Module

Implement the **Student Library API** for the student mobile app by porting and standardizing the library circulation and borrowing functionality from the legacy parent application (`sjai-v6`).

This API provides two main sections:
1. **My Borrow History** (with sub-tabs: *Active Borrowed*, *Overdue*, *History (Returned)*, and *All*, along with tab counters).
2. **Library Books** (circulation desk catalog showing book title, description, applicable grade level, stock counts, and availability status).

---

## User Review Required

> [!IMPORTANT]
> - Shares the exact MySQL schema with `sjai-v6` (`books`, `book_categories`, `book_transactions`, and `book_borrowers` tables).
> - Non-destructive: No destructive migrations (`migrate:fresh`, `db:wipe`) will be executed against MySQL.
> - Automated tests run against SQLite in-memory (`:memory:`), matching `phpunit.xml`.
> - Supports both the authenticated student and linked siblings via `HasSiblingAccess`.

---

## Architecture & Layer Standards

```
Client (Flutter Mobile App)
       │
       ▼
Routes (routes/api.php) [auth:sanctum -> /api/library/*]
       │
       ▼
FormRequests (app/Http/Requests/Library/*) [LibraryBorrowHistoryRequest, LibraryBooksRequest]
       │
       ▼
LibraryController (app/Http/Controllers/LibraryController.php) [Thin Controller]
       │
       ▼
LibraryService (app/Services/LibraryService.php) [Business Logic, Filtering, Inventory Calculations]
       │
       ▼
API Resources (app/Http/Resources/Library/*) [BorrowedBookResource, LibraryBookResource, BookCategoryResource]
       │
       ▼
ResponseApi Trait [Standard JSON Envelope: status, message, data]
```

---

## Proposed Changes

### 1. Models & Relations (`app/Models`)

#### [NEW] [Book.php](file:///Users/aldrich/Desktop/workspace/laravel-projects/flutter-student-api/app/Models/Book.php)
- Represents the `books` table.
- Relationships:
  - `category()`: Belongs to `BookCategory`.
  - `borrowers()`: Has many `BookBorrower`.
  - `activeBorrowers()`: Has many `BookBorrower` where `return_date IS NULL`.
- Scopes & Accessors:
  - `scopeWithInventory()`: Eager loads active borrowed sum via `borrowers as borrowed_qty`.
  - `scopeFilter()`: Filters by search keyword (title, author, isbn, publisher, barcode) and category.
  - `getBorrowedQtyAttribute()`: Computes currently borrowed copies.
  - `getAvailableQtyAttribute()`: `max(0, qty - borrowed_qty)`.
  - `getExtractedGradeLevelAttribute()`: Intelligently detects applicable grade level from title, remarks, or category if applicable, else `null`.

#### [NEW] [BookCategory.php](file:///Users/aldrich/Desktop/workspace/laravel-projects/flutter-student-api/app/Models/BookCategory.php)
- Represents the `book_categories` table.
- Relationships:
  - `books()`: Has many `Book`.
- Scope: `scopeActive()` where `status = 1`.

#### [NEW] [BookTransaction.php](file:///Users/aldrich/Desktop/workspace/laravel-projects/flutter-student-api/app/Models/BookTransaction.php)
- Represents the `book_transactions` table.
- Relationships:
  - `student()`: Belongs to `StudentInformation`.
  - `borrowers()`: Has many `BookBorrower`.

#### [NEW] [BookBorrower.php](file:///Users/aldrich/Desktop/workspace/laravel-projects/flutter-student-api/app/Models/BookBorrower.php)
- Represents the `book_borrowers` table.
- Relationships:
  - `transaction()`: Belongs to `BookTransaction`.
  - `book()`: Belongs to `Book`.
- Helpers:
  - `is_overdue`: `return_date === null && due_date < today`.
  - `is_due_today`: `return_date === null && due_date === today`.
  - `days_overdue`: diff in days if overdue, else 0.
  - `status_label`: 'Returned' (if return_date), 'Overdue' (if overdue), 'Active' (if borrowed & not overdue).

#### [MODIFY] [StudentInformation.php](file:///Users/aldrich/Desktop/workspace/laravel-projects/flutter-student-api/app/Models/StudentInformation.php)
- Add `bookTransactions()` relationship.

---

### 2. Form Requests (`app/Http/Requests/Library`)

#### [NEW] [LibraryBorrowHistoryRequest.php](file:///Users/aldrich/Desktop/workspace/laravel-projects/flutter-student-api/app/Http/Requests/Library/LibraryBorrowHistoryRequest.php)
- Validates:
  - `tab`: `nullable|string|in:all,active,overdue,returned,history` (default: `all`)
  - `keyword` / `search`: `nullable|string|max:100`
  - `student_id`: `nullable|integer` (sibling support)
  - `per_page`: `nullable|integer|min:1|max:100`

#### [NEW] [LibraryBooksRequest.php](file:///Users/aldrich/Desktop/workspace/laravel-projects/flutter-student-api/app/Http/Requests/Library/LibraryBooksRequest.php)
- Validates:
  - `keyword` / `search`: `nullable|string|max:100`
  - `category_id`: `nullable|integer`
  - `grade_level`: `nullable|string|max:50`
  - `available_only`: `nullable|boolean`
  - `per_page`: `nullable|integer|min:1|max:100`

---

### 3. API Resources (`app/Http/Resources/Library`)

#### [NEW] [BorrowedBookResource.php](file:///Users/aldrich/Desktop/workspace/laravel-projects/flutter-student-api/app/Http/Resources/Library/BorrowedBookResource.php)
- Formats borrowed book entry:
  - `id`, `transaction_id`, `book_id`, `title`, `author`, `category`, `category_id`, `isbn`, `qty`, `image`, `image_url`
  - `borrowed_date`, `borrowed_date_formatted`
  - `due_date`, `due_date_formatted`
  - `return_date`, `return_date_formatted`
  - `is_overdue`, `is_due_today`, `days_overdue`
  - `status` (`Active`, `Overdue`, `Returned`)
  - `status_color` (`badge-warning`, `badge-danger`, `badge-success`)
  - `notes`

#### [NEW] [LibraryBookResource.php](file:///Users/aldrich/Desktop/workspace/laravel-projects/flutter-student-api/app/Http/Resources/Library/LibraryBookResource.php)
- Formats library catalog book entry:
  - `id`, `title`, `author`, `category`, `category_id`, `publisher`, `isbn`, `copyright_year`, `barcode`
  - `description` (from remarks or fallback)
  - `remarks`
  - `grade_level` (e.g. "Grade 7", "Grade 11", "SHS", or null if not grade-specific)
  - `is_grade_applicable` (boolean)
  - `total_stock` (`qty`)
  - `borrowed_stock` (`borrowed_qty`)
  - `available_stock` (`available_qty`)
  - `is_available` (boolean)
  - `stock_status` ('In Stock' | 'Low Stock' | 'Out of Stock')
  - `stock_status_color` ('badge-success' | 'badge-warning' | 'badge-danger')
  - `image`, `image_url`

#### [NEW] [BookCategoryResource.php](file:///Users/aldrich/Desktop/workspace/laravel-projects/flutter-student-api/app/Http/Resources/Library/BookCategoryResource.php)
- Formats book categories (`id`, `title`).

---

### 4. Service Layer (`app/Services/LibraryService.php`)

#### [NEW] [LibraryService.php](file:///Users/aldrich/Desktop/workspace/laravel-projects/flutter-student-api/app/Services/LibraryService.php)
- Uses `ResponseApi` and `HasSiblingAccess`.
- Methods:
  - `borrowHistory(LibraryBorrowHistoryRequest $request)`:
    - Resolves target student (self or sibling).
    - Queries `BookBorrower` joined/related through `BookTransaction` where `student_id = $student->id`.
    - Computes tab counts: `all`, `active` (unreturned & not overdue), `overdue` (unreturned & overdue), `returned` (return_date not null).
    - Filters by requested tab (`active`, `overdue`, `returned`, `all`).
    - Paginates and returns standardized JSON with `data`, `counts`, and `pagination`.
  - `borrowDetail(int $id)`:
    - Returns single borrow record detail, verifying student ownership.
  - `books(LibraryBooksRequest $request)`:
    - Queries active books with inventory (`withInventory()`).
    - Filters by keyword, category, available_only, and grade_level.
    - Paginates and returns transformed catalog.
  - `bookDetail(int $id)`:
    - Returns single book details.
  - `categories()`:
    - Returns active book categories for filter dropdowns.

---

### 5. Controller & Routes

#### [NEW] [LibraryController.php](file:///Users/aldrich/Desktop/workspace/laravel-projects/flutter-student-api/app/Http/Controllers/LibraryController.php)
- Thin controller injecting `LibraryService`.
- Exposes `borrowHistory`, `borrowDetail`, `books`, `bookDetail`, `categories`.

#### [MODIFY] [routes/api.php](file:///Users/aldrich/Desktop/workspace/laravel-projects/flutter-student-api/routes/api.php)
- Add `Route::prefix('library')->group(...)` inside `auth:sanctum`.

---

### 6. Automated Testing (Pest PHP with SQLite)

#### [NEW] [LibraryApiTest.php](file:///Users/aldrich/Desktop/workspace/laravel-projects/flutter-student-api/tests/Feature/LibraryApiTest.php)
- Tests:
  - Unauthenticated request returns 401.
  - Student can list borrow history with tab counts.
  - Tab filters work: `active`, `overdue`, `returned`, `all`.
  - Student can fetch single borrow record.
  - Student cannot fetch other student's borrow record unless authorized sibling.
  - Student can list library books with stock counts, availability status, grade level, and description.
  - Books search and category filter work.
  - Single book detail returns inventory details.
  - Book categories endpoint returns active categories.

---

## Verification Plan

### Automated Tests
Run the Pest test suite:
```bash
./vendor/bin/pest tests/Feature/LibraryApiTest.php
```
Run the full test suite to guarantee zero regression:
```bash
php artisan test
```
