<?php

namespace Tests\Feature;

use App\Models\{
    ClassDetail,
    DiscountFee,
    DownpaymentFee,
    Enrollment,
    MiscFee,
    OtherFee,
    PaymentCategory,
    PaymentOther,
    SchoolYear,
    StudentCategory,
    StudentInformation,
    Subscription,
    Transaction,
    TransactionDiscount,
    TransactionMonthlyPayment,
    TransactionOtherFee,
    TuitionFee,
    User
};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentRegistrationApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestTables();
    }

    private function createTestTables(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'username')) {
                    $table->string('username')->nullable();
                }
                if (!Schema::hasColumn('users', 'role')) {
                    $table->integer('role')->default(1);
                }
                if (!Schema::hasColumn('users', 'status')) {
                    $table->tinyInteger('status')->default(1);
                }
            });
        }

        if (!Schema::hasTable('subscriptions')) {
            Schema::create('subscriptions', function (Blueprint $table) {
                $table->increments('id');
                $table->string('subscribable_type');
                $table->unsignedBigInteger('subscribable_id');
                $table->string('player_id');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('school_years')) {
            Schema::create('school_years', function (Blueprint $table) {
                $table->increments('id');
                $table->string('school_year');
                $table->tinyInteger('current')->default(0);
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('student_informations')) {
            Schema::create('student_informations', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('user_id')->nullable();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('email')->nullable();
                $table->string('lrn')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('student_categories')) {
            Schema::create('student_categories', function (Blueprint $table) {
                $table->increments('id');
                $table->string('student_category');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tuition_fees')) {
            Schema::create('tuition_fees', function (Blueprint $table) {
                $table->increments('id');
                $table->decimal('tuition_amt', 10, 2)->default(0);
                $table->tinyInteger('current')->default(1);
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('misc_fees')) {
            Schema::create('misc_fees', function (Blueprint $table) {
                $table->increments('id');
                $table->decimal('misc_amt', 10, 2)->default(0);
                $table->integer('student_cat')->default(1);
                $table->tinyInteger('current')->default(1);
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('other_fees')) {
            Schema::create('other_fees', function (Blueprint $table) {
                $table->increments('id');
                $table->string('other_fee_name');
                $table->decimal('other_fee_amt', 10, 2)->default(0);
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('payment_categories')) {
            Schema::create('payment_categories', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('student_category_id')->default(1);
                $table->integer('grade_level_id');
                $table->unsignedInteger('tuition_fee_id')->nullable();
                $table->unsignedInteger('misc_fee_id')->nullable();
                $table->unsignedInteger('other_fee_id')->nullable();
                $table->integer('months')->default(10);
                $table->tinyInteger('current')->default(1);
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('payment_others')) {
            Schema::create('payment_others', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('payment_category_id');
                $table->unsignedInteger('other_fee_id');
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('downpayment_fees')) {
            Schema::create('downpayment_fees', function (Blueprint $table) {
                $table->increments('id');
                $table->decimal('downpayment_amt', 10, 2);
                $table->integer('grade_level_id');
                $table->string('modified')->nullable();
                $table->tinyInteger('current')->default(1);
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('discount_fees')) {
            Schema::create('discount_fees', function (Blueprint $table) {
                $table->increments('id');
                $table->string('disc_type')->nullable();
                $table->decimal('disc_amt', 10, 2)->default(0);
                $table->integer('category')->default(1);
                $table->tinyInteger('apply_to')->default(1);
                $table->tinyInteger('current')->default(1);
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('transactions')) {
            Schema::create('transactions', function (Blueprint $table) {
                $table->increments('id');
                $table->string('or_no')->nullable();
                $table->unsignedInteger('payment_category_id')->nullable();
                $table->unsignedInteger('student_id');
                $table->unsignedInteger('school_year_id');
                $table->unsignedInteger('downpayment_id')->nullable();
                $table->tinyInteger('isEnrolled')->default(0);
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('transaction_other_fees')) {
            Schema::create('transaction_other_fees', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('transaction_id')->nullable();
                $table->string('or_no')->nullable();
                $table->unsignedInteger('student_id');
                $table->unsignedInteger('others_fee_id')->nullable();
                $table->unsignedInteger('school_year_id');
                $table->string('other_name')->nullable();
                $table->integer('item_qty')->default(1);
                $table->decimal('item_price', 10, 2)->default(0);
                $table->tinyInteger('isSuccess')->default(1);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('transaction_discounts')) {
            Schema::create('transaction_discounts', function (Blueprint $table) {
                $table->increments('id');
                $table->string('or_no')->nullable();
                $table->unsignedInteger('student_id');
                $table->string('discount_type')->nullable();
                $table->integer('category')->default(1);
                $table->decimal('discount_amt', 10, 2)->default(0);
                $table->unsignedInteger('transaction_month_paid_id')->nullable();
                $table->unsignedInteger('school_year_id');
                $table->tinyInteger('isSuccess')->default(1);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('transaction_month_paids')) {
            Schema::create('transaction_month_paids', function (Blueprint $table) {
                $table->increments('id');
                $table->string('or_no')->nullable();
                $table->unsignedInteger('transaction_id')->nullable();
                $table->unsignedInteger('student_id');
                $table->decimal('payment', 10, 2)->default(0);
                $table->unsignedInteger('school_year_id');
                $table->decimal('balance', 10, 2)->default(0);
                $table->decimal('online_charges', 10, 2)->default(0);
                $table->string('email')->nullable();
                $table->string('number')->nullable();
                $table->string('receipt_img')->nullable();
                $table->string('payment_option')->nullable();
                $table->string('approval')->default('Not yet Approved');
                $table->unsignedInteger('downpayment_fee_id')->nullable();
                $table->tinyInteger('isSuccess')->default(1);
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('class_details')) {
            Schema::create('class_details', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('school_year_id')->nullable();
                $table->unsignedInteger('section_id')->nullable();
                $table->unsignedInteger('room_id')->nullable();
                $table->unsignedInteger('adviser_id')->nullable();
                $table->integer('grade_level')->default(7);
                $table->string('term_type')->nullable();
                $table->unsignedInteger('strand_id')->nullable();
                $table->tinyInteger('current')->default(1);
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('section_details')) {
            Schema::create('section_details', function (Blueprint $table) {
                $table->increments('id');
                $table->string('section')->nullable();
                $table->integer('grade_level')->default(7);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('enrollments')) {
            Schema::create('enrollments', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('student_information_id');
                $table->unsignedInteger('class_details_id');
                $table->tinyInteger('current')->default(1);
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('incoming_students')) {
            Schema::create('incoming_students', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('student_id');
                $table->unsignedInteger('school_year_id');
                $table->integer('grade_level_id');
                $table->timestamps();
            });
        }
    }

    public function test_unauthenticated_user_cannot_access_payment_registration(): void
    {
        $response = $this->getJson('/api/payment-registrations');
        $response->assertStatus(401);
    }

    public function test_student_can_fetch_payment_registration_with_correct_balance_and_fees(): void
    {
        $user = User::factory()->create(['username' => '2026-0001']);
        $student = StudentInformation::create([
            'user_id' => $user->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'lrn' => '123456789012',
        ]);

        $sy = SchoolYear::create([
            'school_year' => '2026-2027',
            'current' => 1,
            'status' => 1,
        ]);

        $tuition = TuitionFee::create(['tuition_amt' => 20000, 'status' => 1, 'current' => 1]);
        $misc = MiscFee::create(['misc_amt' => 5000, 'status' => 1, 'current' => 1]);
        $studCat = StudentCategory::create(['student_category' => 'Regular']);

        $otherFee = OtherFee::create(['other_fee_name' => 'Handbook', 'other_fee_amt' => 500, 'status' => 1]);

        $payCat = PaymentCategory::create([
            'student_category_id' => $studCat->id,
            'grade_level_id' => 7,
            'tuition_fee_id' => $tuition->id,
            'misc_fee_id' => $misc->id,
            'status' => 1,
            'current' => 1,
        ]);

        PaymentOther::create([
            'payment_category_id' => $studCat->id,
            'other_fee_id' => $otherFee->id,
            'status' => 1,
        ]);

        DownpaymentFee::create([
            'downpayment_amt' => 8700,
            'grade_level_id' => 7,
            'current' => 1,
            'status' => 1,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/payment-registrations');

        $response->assertStatus(200)
            ->assertJsonPath('results.status', 'UNPAID')
            ->assertJsonPath('results.statusBadge', 'danger')
            ->assertJsonPath('results.grade_level_id', 7)
            ->assertJsonPath('results.total_fees', 25500)
            ->assertJsonPath('results.balance', 25500)
            ->assertJsonPath('results.student_badge_label', 'LRN: 2026-0001 | GRADE 7');
    }

    public function test_student_balance_reflects_approved_payments(): void
    {
        $user = User::factory()->create(['username' => '2026-0002']);
        $student = StudentInformation::create([
            'user_id' => $user->id,
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@example.com',
        ]);

        $sy = SchoolYear::create([
            'school_year' => '2026-2027',
            'current' => 1,
            'status' => 1,
        ]);

        $tuition = TuitionFee::create(['tuition_amt' => 20000, 'status' => 1, 'current' => 1]);
        $misc = MiscFee::create(['misc_amt' => 5000, 'status' => 1, 'current' => 1]);
        $studCat = StudentCategory::create(['student_category' => 'Regular']);

        $payCat = PaymentCategory::create([
            'student_category_id' => $studCat->id,
            'grade_level_id' => 8,
            'tuition_fee_id' => $tuition->id,
            'misc_fee_id' => $misc->id,
            'status' => 1,
            'current' => 1,
        ]);

        $transaction = Transaction::create([
            'student_id' => $student->id,
            'school_year_id' => $sy->id,
            'payment_category_id' => $payCat->id,
            'status' => 1,
        ]);

        // Create an approved monthly payment of 10,000
        TransactionMonthlyPayment::create([
            'student_id' => $student->id,
            'school_year_id' => $sy->id,
            'transaction_id' => $transaction->id,
            'payment' => 10000,
            'balance' => 15000,
            'approval' => 'Approved',
            'isSuccess' => 1,
        ]);

        // Create an unapproved monthly payment of 5,000 (should NOT be subtracted yet)
        TransactionMonthlyPayment::create([
            'student_id' => $student->id,
            'school_year_id' => $sy->id,
            'transaction_id' => $transaction->id,
            'payment' => 5000,
            'balance' => 10000,
            'approval' => 'Not yet Approved',
            'isSuccess' => 1,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/payment-registrations');

        $response->assertStatus(200)
            ->assertJsonPath('results.total_fees', 25000)
            ->assertJsonPath('results.balance', 15000)
            ->assertJsonPath('results.status', 'UNPAID')
            ->assertJsonPath('results.previousTransactionStatus', 'Not yet Approved');
    }

    public function test_transaction_logs_endpoint_returns_history(): void
    {
        $user = User::factory()->create();
        $student = StudentInformation::create([
            'user_id' => $user->id,
            'first_name' => 'Alice',
            'last_name' => 'Wong',
            'email' => 'alice@example.com',
        ]);

        $sy = SchoolYear::create([
            'school_year' => '2026-2027',
            'current' => 1,
            'status' => 1,
        ]);

        $tuition = TuitionFee::create(['tuition_amt' => 15000, 'status' => 1, 'current' => 1]);
        $misc = MiscFee::create(['misc_amt' => 3000, 'status' => 1, 'current' => 1]);
        $studCat = StudentCategory::create(['student_category' => 'Regular']);

        $payCat = PaymentCategory::create([
            'student_category_id' => $studCat->id,
            'grade_level_id' => 7,
            'tuition_fee_id' => $tuition->id,
            'misc_fee_id' => $misc->id,
            'status' => 1,
            'current' => 1,
        ]);

        $transaction = Transaction::create([
            'student_id' => $student->id,
            'school_year_id' => $sy->id,
            'payment_category_id' => $payCat->id,
            'status' => 1,
        ]);

        TransactionMonthlyPayment::create([
            'student_id' => $student->id,
            'school_year_id' => $sy->id,
            'transaction_id' => $transaction->id,
            'payment' => 5000,
            'balance' => 13000,
            'payment_option' => 'China Bank',
            'approval' => 'Approved',
            'isSuccess' => 1,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/payment-registrations/transaction-logs');

        $response->assertStatus(200)
            ->assertJsonPath('results.transactions.0.payment', '5,000.00')
            ->assertJsonPath('results.transactions.0.statusValue', 'Approved');
    }

    public function test_student_can_fetch_lightweight_balance(): void
    {
        $user = User::factory()->create(['username' => '2026-0003']);
        $student = StudentInformation::create([
            'user_id' => $user->id,
            'first_name' => 'Charlie',
            'last_name' => 'Brown',
            'email' => 'charlie@example.com',
            'lrn' => '123456789013',
        ]);

        $sy = SchoolYear::create([
            'school_year' => '2026-2027',
            'current' => 1,
            'status' => 1,
        ]);

        $tuition = TuitionFee::create(['tuition_amt' => 20000, 'status' => 1, 'current' => 1]);
        $misc = MiscFee::create(['misc_amt' => 5000, 'status' => 1, 'current' => 1]);
        $studCat = StudentCategory::create(['student_category' => 'Regular']);

        PaymentCategory::create([
            'student_category_id' => $studCat->id,
            'grade_level_id' => 7,
            'tuition_fee_id' => $tuition->id,
            'misc_fee_id' => $misc->id,
            'status' => 1,
            'current' => 1,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/payment-registrations/status');

        $response->assertStatus(200)
            ->assertJsonPath('results.balance', 25000)
            ->assertJsonPath('results.formatted_balance', '₱ 25,000.00')
            ->assertJsonPath('results.status', 'UNPAID')
            ->assertJsonPath('results.statusBadge', 'danger')
            ->assertJsonPath('results.total_fees', 25000)
            ->assertJsonPath('results.total_paid', 0)
            ->assertJsonPath('results.grade_level_id', 7)
            ->assertJsonPath('results.is_pending_approval', false)
            ->assertJsonPath('results.can_submit', true)
            ->assertJsonPath('results.has_balance_prev_school_year', false);
    }

    public function test_pending_approval_disables_submission_and_provides_message(): void
    {
        $user = User::factory()->create(['username' => '2026-0004']);
        $student = StudentInformation::create([
            'user_id' => $user->id,
            'first_name' => 'David',
            'last_name' => 'Miller',
            'email' => 'david@example.com',
            'lrn' => '123456789014',
        ]);

        $sy = SchoolYear::create([
            'school_year' => '2026-2027',
            'current' => 1,
            'status' => 1,
        ]);

        $tuition = TuitionFee::create(['tuition_amt' => 20000, 'status' => 1, 'current' => 1]);
        $misc = MiscFee::create(['misc_amt' => 5000, 'status' => 1, 'current' => 1]);
        $studCat = StudentCategory::create(['student_category' => 'Regular']);

        $payCat = PaymentCategory::create([
            'student_category_id' => $studCat->id,
            'grade_level_id' => 7,
            'tuition_fee_id' => $tuition->id,
            'misc_fee_id' => $misc->id,
            'status' => 1,
            'current' => 1,
        ]);

        $transaction = Transaction::create([
            'student_id' => $student->id,
            'school_year_id' => $sy->id,
            'payment_category_id' => $payCat->id,
            'status' => 1,
        ]);

        // Add pending payment
        TransactionMonthlyPayment::create([
            'student_id' => $student->id,
            'school_year_id' => $sy->id,
            'transaction_id' => $transaction->id,
            'payment' => 5000,
            'balance' => 20000,
            'approval' => 'Not yet Approved',
            'isSuccess' => 1,
        ]);

        Sanctum::actingAs($user);

        // Check status endpoint flags
        $response = $this->getJson('/api/payment-registrations/status');
        $response->assertStatus(200)
            ->assertJsonPath('results.is_pending_approval', true)
            ->assertJsonPath('results.can_submit', false)
            ->assertJsonPath('results.previousTransactionStatus', 'Not yet Approved')
            ->assertJsonPath('results.pending_approval_message', 'Please wait for the payment approval by our Finance department. You may check your previous transaction by clicking or tapping the View Account Details button. Thank you for your understanding.');

        // Attempting to submit while pending should return 400 error with message
        $storeResponse = $this->postJson('/api/payment-registrations', [
            'or_no' => 'OR-9999',
            'payment_fee' => 5000,
            'school_year_id' => $sy->id,
            'email' => 'david@example.com',
            'payment_option' => 'China Bank',
            'receipt_image' => UploadedFile::fake()->create('receipt.jpg', 100, 'image/jpeg'),
        ]);

        $storeResponse->assertStatus(400)
            ->assertJsonPath('message', 'Please wait for the payment approval by our Finance department. You may check your previous transaction by clicking or tapping the View Account Details button. Thank you for your understanding.');
    }

    public function test_submitting_payment_receipt_notifies_active_finance_users_and_triggers_onesignal(): void
    {
        $studentUser = User::factory()->create(['username' => '2026-0005', 'role' => 1, 'status' => 1]);
        $student = StudentInformation::create([
            'user_id' => $studentUser->id,
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'email' => 'maria@example.com',
            'lrn' => '987654321012',
        ]);

        $sy = SchoolYear::create([
            'school_year' => '2026-2027',
            'current' => 1,
            'status' => 1,
        ]);

        $tuition = TuitionFee::create(['tuition_amt' => 25000, 'status' => 1, 'current' => 1]);
        $misc = MiscFee::create(['misc_amt' => 6000, 'status' => 1, 'current' => 1]);
        $studCat = StudentCategory::create(['student_category' => 'Regular']);

        PaymentCategory::create([
            'student_category_id' => $studCat->id,
            'grade_level_id' => 7,
            'tuition_fee_id' => $tuition->id,
            'misc_fee_id' => $misc->id,
            'status' => 1,
            'current' => 1,
        ]);

        // Active Finance user
        $financeUser = User::factory()->create([
            'username' => 'finance_user_1',
            'email' => 'finance1@example.com',
            'role' => 6,
            'status' => 1,
        ]);

        // Inactive Finance user (should NOT receive notification)
        $inactiveFinanceUser = User::factory()->create([
            'username' => 'finance_inactive',
            'email' => 'inactive_fin@example.com',
            'role' => 6,
            'status' => 0,
        ]);

        // Non-finance user (teacher / role 2)
        $teacherUser = User::factory()->create([
            'username' => 'teacher_1',
            'email' => 'teacher@example.com',
            'role' => 2,
            'status' => 1,
        ]);

        // Subscribe active finance user to OneSignal
        Subscription::create([
            'subscribable_type' => User::class,
            'subscribable_id' => $financeUser->id,
            'player_id' => 'finance-player-uuid-12345',
        ]);

        config([
            'one-signal.url' => 'https://onesignal.com/api/v1/',
            'one-signal.app_id' => 'test-onesignal-app-id',
            'one-signal.authorize' => 'test-onesignal-auth-key',
        ]);

        Http::fake([
            'https://onesignal.com/api/v1/notifications' => Http::response(['id' => 'notif-123', 'recipients' => 1], 200),
        ]);

        Sanctum::actingAs($studentUser);

        $response = $this->postJson('/api/payment-registrations', [
            'or_no' => 'OR-2026-888',
            'payment_fee' => 12500,
            'school_year_id' => $sy->id,
            'email' => 'maria@example.com',
            'payment_option' => 'Metrobank',
            'receipt_image' => UploadedFile::fake()->create('receipt.png', 150, 'image/png'),
        ]);

        $response->assertStatus(200);

        // 1. Verify Database Notification saved for active finance user
        $this->assertEquals(1, $financeUser->notifications()->count());
        $notification = $financeUser->notifications()->first();
        $this->assertEquals('App\Notifications\StudentPaymentRegisteredNotification', $notification->type);

        $notifData = $notification->data;
        $this->assertEquals('Payment Notification', $notifData['title']);
        $this->assertEquals('student_payment_registration', $notifData['type']);
        $this->assertEquals('Maria Santos', $notifData['student_name']);
        $this->assertEquals('OR-2026-888', $notifData['or_no']);
        $this->assertEquals(12500, $notifData['amount']);
        $this->assertStringContainsString('Maria Santos', $notifData['message']);
        $this->assertStringContainsString('OR# OR-2026-888', $notifData['message']);
        $this->assertStringContainsString('12,500.00', $notifData['message']);

        // 2. Verify Inactive and Non-Finance users received 0 notifications
        $this->assertEquals(0, $inactiveFinanceUser->notifications()->count());
        $this->assertEquals(0, $teacherUser->notifications()->count());

        // 3. Verify OneSignal Push Notification was sent
        Http::assertSent(function ($request) {
            $data = $request->data();
            return $request->url() === 'https://onesignal.com/api/v1/notifications'
                && ($data['include_player_ids'] ?? []) === ['finance-player-uuid-12345']
                && ($data['app_id'] ?? '') === 'test-onesignal-app-id'
                && ($data['headings']['en'] ?? '') === 'Payment Notification'
                && str_contains($data['contents']['en'] ?? '', 'Maria Santos')
                && str_contains($data['contents']['en'] ?? '', 'OR# OR-2026-888')
                && str_contains($data['contents']['en'] ?? '', '12,500.00');
        });
    }

    public function test_submitting_payment_receipt_succeeds_even_when_onesignal_fails(): void
    {
        $studentUser = User::factory()->create(['username' => '2026-0006', 'role' => 1, 'status' => 1]);
        $student = StudentInformation::create([
            'user_id' => $studentUser->id,
            'first_name' => 'Alex',
            'last_name' => 'Cruz',
            'email' => 'alex@example.com',
            'lrn' => '111222333444',
        ]);

        $sy = SchoolYear::create([
            'school_year' => '2026-2027',
            'current' => 1,
            'status' => 1,
        ]);

        $tuition = TuitionFee::create(['tuition_amt' => 20000, 'status' => 1, 'current' => 1]);
        $misc = MiscFee::create(['misc_amt' => 5000, 'status' => 1, 'current' => 1]);
        $studCat = StudentCategory::create(['student_category' => 'Regular']);

        PaymentCategory::create([
            'student_category_id' => $studCat->id,
            'grade_level_id' => 7,
            'tuition_fee_id' => $tuition->id,
            'misc_fee_id' => $misc->id,
            'status' => 1,
            'current' => 1,
        ]);

        // Active Finance user
        $financeUser = User::factory()->create([
            'role' => 6,
            'status' => 1,
        ]);

        Subscription::create([
            'subscribable_type' => User::class,
            'subscribable_id' => $financeUser->id,
            'player_id' => 'finance-player-fail-test',
        ]);

        config([
            'one-signal.url' => 'https://onesignal.com/api/v1/',
            'one-signal.app_id' => 'test-app-id',
            'one-signal.authorize' => 'test-auth-key',
        ]);

        // Mock OneSignal returning a 500 error
        Http::fake([
            'https://onesignal.com/api/v1/notifications' => Http::response(['error' => 'Internal Server Error'], 500),
        ]);

        Sanctum::actingAs($studentUser);

        $response = $this->postJson('/api/payment-registrations', [
            'or_no' => 'OR-FAIL-TEST',
            'payment_fee' => 5000,
            'school_year_id' => $sy->id,
            'email' => 'alex@example.com',
            'payment_option' => 'GCash',
            'receipt_image' => UploadedFile::fake()->create('receipt.jpg', 100, 'image/jpeg'),
        ]);

        $response->assertStatus(200);

        // Transaction and database notification still succeed
        $this->assertDatabaseHas('transaction_month_paids', [
            'or_no' => 'OR-FAIL-TEST',
            'student_id' => $student->id,
            'payment' => 5000,
        ]);
        $this->assertEquals(1, $financeUser->notifications()->count());
    }
}


