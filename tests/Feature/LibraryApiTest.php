<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookBorrower;
use App\Models\BookCategory;
use App\Models\BookTransaction;
use App\Models\SiblingGroup;
use App\Models\SiblingGroupMember;
use App\Models\StudentInformation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LibraryApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestTables();
    }

    private function createTestTables(): void
    {
        if (! Schema::hasTable('student_informations')) {
            Schema::create('student_informations', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('user_id')->nullable();
                $table->string('first_name')->nullable();
                $table->string('middle_name')->nullable();
                $table->string('last_name')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('book_categories')) {
            Schema::create('book_categories', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('title');
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('books')) {
            Schema::create('books', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('title')->nullable();
                $table->integer('category_id')->nullable();
                $table->string('author')->nullable();
                $table->string('author_2')->nullable();
                $table->string('author_3')->nullable();
                $table->string('author_4')->nullable();
                $table->string('author_5')->nullable();
                $table->integer('qty')->nullable();
                $table->string('pub')->nullable();
                $table->string('publisher_name')->nullable();
                $table->string('isbn')->nullable();
                $table->integer('copyright_year')->nullable();
                $table->string('book_status')->nullable();
                $table->string('book_barcode')->nullable();
                $table->string('image')->nullable();
                $table->dateTime('date_added')->nullable();
                $table->string('remarks')->nullable();
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('book_transactions')) {
            Schema::create('book_transactions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->integer('student_id');
                $table->integer('total_qty');
                $table->tinyInteger('status')->default(1);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('book_borrowers')) {
            Schema::create('book_borrowers', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->integer('book_transactions_id');
                $table->integer('book_id');
                $table->integer('qty')->default(1);
                $table->date('due_date')->nullable();
                $table->dateTime('return_date')->nullable();
                $table->integer('created_by')->default(1);
                $table->integer('modified_by')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('sibling_groups')) {
            Schema::create('sibling_groups', function (Blueprint $table) {
                $table->increments('id');
                $table->string('group_code')->unique();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('sibling_group_members')) {
            Schema::create('sibling_group_members', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('sibling_group_id');
                $table->unsignedInteger('student_information_id');
                $table->timestamps();
            });
        }
    }

    public function test_unauthenticated_user_cannot_access_library_endpoints(): void
    {
        $this->getJson('/api/library/borrow-history')->assertUnauthorized();
        $this->getJson('/api/library/books')->assertUnauthorized();
        $this->getJson('/api/library/categories')->assertUnauthorized();
    }

    public function test_user_without_student_record_returns_404(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/library/borrow-history');

        $response->assertNotFound()
            ->assertJson([
                'code' => 404,
                'message' => 'Student information not found.',
            ]);
    }

    public function test_student_can_list_borrow_history_with_tab_counts(): void
    {
        $user = User::factory()->create();
        $student = StudentInformation::create([
            'user_id' => $user->id,
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
        ]);
        Sanctum::actingAs($user);

        $category = BookCategory::create(['title' => 'Mathematics', 'status' => 1]);

        $book1 = Book::create([
            'title' => 'Grade 7 Mathematics',
            'category_id' => $category->id,
            'author' => 'Author A',
            'qty' => 5,
            'status' => 1,
        ]);
        $book2 = Book::create([
            'title' => 'Science Grade 10',
            'category_id' => $category->id,
            'author' => 'Author B',
            'qty' => 3,
            'status' => 1,
        ]);
        $book3 = Book::create([
            'title' => 'English Grammar',
            'category_id' => $category->id,
            'author' => 'Author C',
            'qty' => 4,
            'status' => 1,
        ]);

        $transaction = BookTransaction::create([
            'student_id' => $student->id,
            'total_qty' => 3,
            'status' => 1,
        ]);

        // 1 active (due next week, return_date null)
        BookBorrower::create([
            'book_transactions_id' => $transaction->id,
            'book_id' => $book1->id,
            'qty' => 1,
            'due_date' => Carbon::today()->addDays(7)->format('Y-m-d'),
            'return_date' => null,
            'created_by' => 1,
        ]);

        // 1 overdue (due 3 days ago, return_date null)
        BookBorrower::create([
            'book_transactions_id' => $transaction->id,
            'book_id' => $book2->id,
            'qty' => 1,
            'due_date' => Carbon::today()->subDays(3)->format('Y-m-d'),
            'return_date' => null,
            'created_by' => 1,
        ]);

        // 1 returned
        BookBorrower::create([
            'book_transactions_id' => $transaction->id,
            'book_id' => $book3->id,
            'qty' => 1,
            'due_date' => Carbon::today()->format('Y-m-d'),
            'return_date' => Carbon::today()->subDay()->format('Y-m-d H:i:s'),
            'created_by' => 1,
        ]);

        $response = $this->getJson('/api/library/borrow-history');

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'code',
                'results' => [
                    'data' => [
                        '*' => [
                            'id',
                            'transaction_id',
                            'book_id',
                            'title',
                            'author',
                            'category',
                            'qty',
                            'borrowed_date',
                            'due_date',
                            'return_date',
                            'is_overdue',
                            'is_due_today',
                            'days_overdue',
                            'status',
                            'status_color',
                        ],
                    ],
                    'counts' => [
                        'all',
                        'active',
                        'overdue',
                        'returned',
                    ],
                    'active_tab',
                    'pagination',
                ],
            ]);

        $response->assertJson([
            'code' => 200,
            'results' => [
                'counts' => [
                    'all' => 3,
                    'active' => 1,
                    'overdue' => 1,
                    'returned' => 1,
                ],
                'active_tab' => 'all',
            ],
        ]);

        $this->assertCount(3, $response->json('results.data'));
    }

    public function test_student_can_filter_borrow_history_by_tab(): void
    {
        $user = User::factory()->create();
        $student = StudentInformation::create([
            'user_id' => $user->id,
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
        ]);
        Sanctum::actingAs($user);

        $book = Book::create(['title' => 'Algebra 1', 'qty' => 5, 'status' => 1]);
        $transaction = BookTransaction::create(['student_id' => $student->id, 'total_qty' => 3, 'status' => 1]);

        BookBorrower::create([
            'book_transactions_id' => $transaction->id,
            'book_id' => $book->id,
            'qty' => 1,
            'due_date' => Carbon::today()->addDays(5)->format('Y-m-d'),
            'return_date' => null,
            'created_by' => 1,
        ]);

        BookBorrower::create([
            'book_transactions_id' => $transaction->id,
            'book_id' => $book->id,
            'qty' => 1,
            'due_date' => Carbon::today()->subDays(4)->format('Y-m-d'),
            'return_date' => null,
            'created_by' => 1,
        ]);

        BookBorrower::create([
            'book_transactions_id' => $transaction->id,
            'book_id' => $book->id,
            'qty' => 1,
            'due_date' => Carbon::today()->format('Y-m-d'),
            'return_date' => Carbon::today()->subDays(2)->format('Y-m-d H:i:s'),
            'created_by' => 1,
        ]);

        // Active tab
        $activeRes = $this->getJson('/api/library/borrow-history?tab=active');
        $activeRes->assertOk();
        $this->assertCount(1, $activeRes->json('results.data'));
        $this->assertEquals('Active', $activeRes->json('results.data.0.status'));
        $this->assertFalse($activeRes->json('results.data.0.is_overdue'));

        // Overdue tab
        $overdueRes = $this->getJson('/api/library/borrow-history?tab=overdue');
        $overdueRes->assertOk();
        $this->assertCount(1, $overdueRes->json('results.data'));
        $this->assertEquals('Overdue', $overdueRes->json('results.data.0.status'));
        $this->assertTrue($overdueRes->json('results.data.0.is_overdue'));
        $this->assertEquals(4, $overdueRes->json('results.data.0.days_overdue'));

        // Returned / history tab
        $returnedRes = $this->getJson('/api/library/borrow-history?tab=returned');
        $returnedRes->assertOk();
        $this->assertCount(1, $returnedRes->json('results.data'));
        $this->assertEquals('Returned', $returnedRes->json('results.data.0.status'));
        $this->assertNotNull($returnedRes->json('results.data.0.return_date'));
    }

    public function test_student_can_fetch_single_borrow_record(): void
    {
        $user = User::factory()->create();
        $student = StudentInformation::create([
            'user_id' => $user->id,
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
        ]);
        Sanctum::actingAs($user);

        $book = Book::create(['title' => 'Calculus Early Transcendentals', 'qty' => 2, 'status' => 1]);
        $transaction = BookTransaction::create(['student_id' => $student->id, 'total_qty' => 1, 'status' => 1]);
        $borrower = BookBorrower::create([
            'book_transactions_id' => $transaction->id,
            'book_id' => $book->id,
            'qty' => 1,
            'due_date' => Carbon::today()->addDays(3)->format('Y-m-d'),
            'created_by' => 1,
        ]);

        $response = $this->getJson('/api/library/borrow-history/' . $borrower->id);

        $response->assertOk()
            ->assertJson([
                'code' => 200,
                'results' => [
                    'id' => $borrower->id,
                    'title' => 'Calculus Early Transcendentals',
                    'status' => 'Active',
                ],
            ]);
    }

    public function test_student_cannot_view_unrelated_student_borrow_record(): void
    {
        $user = User::factory()->create();
        StudentInformation::create([
            'user_id' => $user->id,
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
        ]);
        Sanctum::actingAs($user);

        $otherStudent = StudentInformation::create([
            'user_id' => 9999,
            'first_name' => 'Maria',
            'last_name' => 'Santos',
        ]);

        $book = Book::create(['title' => 'Physics I', 'qty' => 3, 'status' => 1]);
        $transaction = BookTransaction::create(['student_id' => $otherStudent->id, 'total_qty' => 1, 'status' => 1]);
        $borrower = BookBorrower::create([
            'book_transactions_id' => $transaction->id,
            'book_id' => $book->id,
            'qty' => 1,
            'due_date' => Carbon::today()->addDays(3)->format('Y-m-d'),
            'created_by' => 1,
        ]);

        $this->getJson('/api/library/borrow-history/' . $borrower->id)->assertNotFound();
    }

    public function test_student_can_access_sibling_borrow_history(): void
    {
        $user = User::factory()->create();
        $student1 = StudentInformation::create(['user_id' => $user->id, 'first_name' => 'Sibling', 'last_name' => 'One']);
        $student2 = StudentInformation::create(['user_id' => 8888, 'first_name' => 'Sibling', 'last_name' => 'Two']);
        Sanctum::actingAs($user);

        $group = SiblingGroup::create(['group_code' => 'GRP-LIB-100']);
        SiblingGroupMember::create(['sibling_group_id' => $group->id, 'student_information_id' => $student1->id]);
        SiblingGroupMember::create(['sibling_group_id' => $group->id, 'student_information_id' => $student2->id]);

        $book = Book::create(['title' => 'Chemistry', 'qty' => 2, 'status' => 1]);
        $transaction = BookTransaction::create(['student_id' => $student2->id, 'total_qty' => 1, 'status' => 1]);
        BookBorrower::create([
            'book_transactions_id' => $transaction->id,
            'book_id' => $book->id,
            'qty' => 1,
            'due_date' => Carbon::today()->addDays(5)->format('Y-m-d'),
            'created_by' => 1,
        ]);

        $response = $this->getJson('/api/library/borrow-history?student_id=' . $student2->id);

        $response->assertOk()
            ->assertJson([
                'code' => 200,
                'results' => [
                    'counts' => [
                        'all' => 1,
                        'active' => 1,
                    ],
                ],
            ]);
    }

    public function test_student_can_list_library_books_with_stock_status_and_grade_level(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $category = BookCategory::create(['title' => 'Mathematics', 'status' => 1]);
        $shsCategory = BookCategory::create(['title' => 'SHS', 'status' => 1]);

        // Book 1: Grade 7 Mathematics, qty 5, 0 borrowed -> In Stock, Grade 7
        $book1 = Book::create([
            'title' => 'Grade 7 Mathematics: Patterns and Practicalities',
            'category_id' => $category->id,
            'author' => 'Author One',
            'qty' => 5,
            'remarks' => 'Revised K12 edition',
            'status' => 1,
        ]);

        // Book 2: SHS Book, qty 2, 2 active borrowed -> Out of Stock, SHS
        $book2 = Book::create([
            'title' => 'General Biology',
            'category_id' => $shsCategory->id,
            'author' => 'Author Two',
            'qty' => 2,
            'remarks' => 'SHS Core Subject',
            'status' => 1,
        ]);

        $trans = BookTransaction::create(['student_id' => 1, 'total_qty' => 2, 'status' => 1]);
        BookBorrower::create([
            'book_transactions_id' => $trans->id,
            'book_id' => $book2->id,
            'qty' => 2,
            'due_date' => Carbon::today()->addDays(2)->format('Y-m-d'),
            'return_date' => null,
            'created_by' => 1,
        ]);

        // Book 3: General fiction, no grade
        $book3 = Book::create([
            'title' => 'The Great Gatsby',
            'category_id' => $category->id,
            'author' => 'F. Scott Fitzgerald',
            'qty' => 10,
            'status' => 1,
        ]);

        $response = $this->getJson('/api/library/books');

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'code',
                'results' => [
                    'data' => [
                        '*' => [
                            'id',
                            'title',
                            'author',
                            'category',
                            'category_id',
                            'description',
                            'remarks',
                            'grade_level',
                            'is_grade_applicable',
                            'total_stock',
                            'borrowed_stock',
                            'available_stock',
                            'is_available',
                            'stock_status',
                            'stock_status_color',
                        ],
                    ],
                    'pagination',
                ],
            ]);

        $books = collect($response->json('results.data'));

        $b1 = $books->firstWhere('id', $book1->id);
        $this->assertEquals('Grade 7', $b1['grade_level']);
        $this->assertTrue($b1['is_grade_applicable']);
        $this->assertEquals('Revised K12 edition', $b1['description']);
        $this->assertEquals(5, $b1['available_stock']);
        $this->assertTrue($b1['is_available']);
        $this->assertEquals('In Stock', $b1['stock_status']);

        $b2 = $books->firstWhere('id', $book2->id);
        $this->assertEquals('Senior High School (SHS)', $b2['grade_level']);
        $this->assertTrue($b2['is_grade_applicable']);
        $this->assertEquals(0, $b2['available_stock']);
        $this->assertFalse($b2['is_available']);
        $this->assertEquals('Out of Stock', $b2['stock_status']);

        $b3 = $books->firstWhere('id', $book3->id);
        $this->assertNull($b3['grade_level']);
        $this->assertFalse($b3['is_grade_applicable']);
    }

    public function test_student_can_filter_books_by_keyword_and_category_and_availability(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $cat1 = BookCategory::create(['title' => 'Math', 'status' => 1]);
        $cat2 = BookCategory::create(['title' => 'Science', 'status' => 1]);

        $book1 = Book::create(['title' => 'Calculus I', 'category_id' => $cat1->id, 'qty' => 5, 'status' => 1]);
        $book2 = Book::create(['title' => 'Physics Mechanics', 'category_id' => $cat2->id, 'qty' => 3, 'status' => 1]);
        $book3 = Book::create(['title' => 'Chemistry Basics', 'category_id' => $cat2->id, 'qty' => 1, 'status' => 1]);

        // Borrow out book 3 completely
        $trans = BookTransaction::create(['student_id' => 1, 'total_qty' => 1, 'status' => 1]);
        BookBorrower::create([
            'book_transactions_id' => $trans->id,
            'book_id' => $book3->id,
            'qty' => 1,
            'due_date' => Carbon::today()->addDays(3)->format('Y-m-d'),
            'return_date' => null,
            'created_by' => 1,
        ]);

        // Search keyword
        $searchRes = $this->getJson('/api/library/books?search=Calculus');
        $searchRes->assertOk();
        $this->assertCount(1, $searchRes->json('results.data'));
        $this->assertEquals('Calculus I', $searchRes->json('results.data.0.title'));

        // Category filter
        $catRes = $this->getJson('/api/library/books?category_id=' . $cat2->id);
        $catRes->assertOk();
        $this->assertCount(2, $catRes->json('results.data'));

        // Available only filter
        $availRes = $this->getJson('/api/library/books?category_id=' . $cat2->id . '&available_only=1');
        $availRes->assertOk();
        $this->assertCount(1, $availRes->json('results.data'));
        $this->assertEquals('Physics Mechanics', $availRes->json('results.data.0.title'));
    }

    public function test_student_can_view_single_book_detail(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $book = Book::create([
            'title' => 'World History',
            'qty' => 4,
            'author' => 'Historian X',
            'remarks' => 'Comprehensive edition',
            'status' => 1,
        ]);

        $response = $this->getJson('/api/library/books/' . $book->id);

        $response->assertOk()
            ->assertJson([
                'code' => 200,
                'results' => [
                    'id' => $book->id,
                    'title' => 'World History',
                    'author' => 'Historian X',
                    'description' => 'Comprehensive edition',
                    'available_stock' => 4,
                    'is_available' => true,
                ],
            ]);

        $this->getJson('/api/library/books/99999')->assertNotFound();
    }

    public function test_student_can_list_active_book_categories(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        BookCategory::create(['title' => 'English', 'status' => 1]);
        BookCategory::create(['title' => 'Mathematics', 'status' => 1]);
        BookCategory::create(['title' => 'Archived Category', 'status' => 0]);

        $response = $this->getJson('/api/library/categories');

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'code',
                'results' => [
                    '*' => ['id', 'title'],
                ],
            ]);

        $categories = $response->json('results');
        $this->assertCount(2, $categories);
        $this->assertEquals('English', $categories[0]['title']);
        $this->assertEquals('Mathematics', $categories[1]['title']);
    }
}
