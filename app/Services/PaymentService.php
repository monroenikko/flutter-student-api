<?php

namespace App\Services;

use App\Enums\BankEnum;
use App\Enums\StatusEnum;
use App\Helpers\PaymentNotificationHelper;
use App\Http\Resources\CheckDiscountResource;
use App\Models\{
    ClassDetail,
    DiscountFee,
    DownpaymentFee,
    Enrollment,
    IncomingStudent,
    PaymentCategory,
    PaymentOther,
    SchoolYear,
    StudentInformation,
    Transaction,
    TransactionDiscount,
    TransactionMonthlyPayment,
    TransactionOtherFee,
    User
};
use App\Traits\ResponseApi;
use App\Traits\HasSiblingAccess;
use App\Traits\SchoolYear as HasSchoolYear;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    use HasSchoolYear, ResponseApi, HasSiblingAccess;

    protected $students;

    const FULL_PAYMENT = ['id' => 1101, 'description' => 'Full Payment', 'downpayment_amt' => 0];
    const OTHER_PAYMENT = ['id' => 1102, 'description' => 'Other Amount. (NOTE: payment should not be lower than 9,800)', 'downpayment_amt' => 9800];

    const PENDING_APPROVAL_MSG = 'Please wait for the payment approval by our Finance department. You may check your previous transaction by clicking or tapping the View Account Details button. Thank you for your understanding.';

    public function __construct(StudentInformation $students)
    {
        $this->students = $students;
    }

    /**
     * Lightweight status endpoint returning student balance, pending approval flags, and prompt messages.
     */
    public function status($request)
    {
        $student = $this->getAuthorizedStudent(request('student_id'));

        if (!$student) {
            throw new Exception('Student information not found.');
        }

        $schoolYear = $this->activeSchoolYear();

        if (!$schoolYear) {
            throw new Exception('Active school year not found.');
        }

        // Previous active school year and previous transaction
        $previousUserID = SchoolYear::where('id', '<', $schoolYear->id)
            ->where('status', 1)
            ->max('id');

        $previousYear = $previousUserID
            ? Transaction::where('student_id', $student->id)
                ->where('school_year_id', $previousUserID)
                ->first()
            : null;

        $enrollment = $this->checkEnrollment($student->id);
        $currentEnrollment = $this->hasEnrollment($student->id, $schoolYear->id);
        $currentTransaction = $this->getTransaction($student->id, $schoolYear->id);

        $transactionStatus = ($currentEnrollment !== null)
            ? ($currentTransaction ? (int) $currentTransaction->status : 0)
            : 1;

        $grade_level_id = $enrollment->grade_level ?? 7;

        if ($enrollment && $enrollment->grade_level <= 12) {
            if ($currentEnrollment !== null) {
                $grade_level_id = $enrollment->grade_level;
            } elseif ($previousYear && isset($previousYear->school_year_id)) {
                $prevEnrollment = $this->hasEnrollment($student->id, $previousYear->school_year_id);
                if ($prevEnrollment !== null) {
                    $grade_level_id = $prevEnrollment->grade_level + 1;
                }
            }
        }

        $hasPreviousUnpaid = isset($previousYear->status) && (int) $previousYear->status !== 0;
        $targetSchoolYearId = $hasPreviousUnpaid ? $previousYear->school_year_id : $schoolYear->id;
        $hasTransaction = $hasPreviousUnpaid ? $previousYear : $currentTransaction;

        if ($hasTransaction) {
            $hasSchoolYearEnrollment = $this->checkSchoolYearEnrollment($student->id, $targetSchoolYearId);

            if (isset($hasSchoolYearEnrollment->grade_level)) {
                $grade_level_id = $hasSchoolYearEnrollment->grade_level;
            } elseif (isset($hasTransaction->paymentCategory->grade_level_id)) {
                $grade_level_id = $hasTransaction->paymentCategory->grade_level_id;
            } else {
                $prevSchoolYearEnrollment = $previousUserID
                    ? $this->checkSchoolYearEnrollment($student->id, $previousUserID)
                    : null;

                if ($prevSchoolYearEnrollment && isset($prevSchoolYearEnrollment->grade_level)) {
                    $grade_level_id = $prevSchoolYearEnrollment->grade_level + $transactionStatus;
                } else {
                    $incomingStudent = IncomingStudent::where('student_id', $student->id)
                        ->where('school_year_id', $schoolYear->id)
                        ->first();
                    $grade_level_id = $incomingStudent->grade_level_id ?? $grade_level_id;
                }
            }
        } else {
            $hasSchoolYearEnrollment = $this->checkSchoolYearEnrollment($student->id, $schoolYear->id);

            if ($hasSchoolYearEnrollment && isset($hasSchoolYearEnrollment->grade_level)) {
                $grade_level_id = $hasSchoolYearEnrollment->grade_level;
            } else {
                $incomingStudent = IncomingStudent::where('student_id', $student->id)
                    ->where('school_year_id', $schoolYear->id)
                    ->first();
                if ($incomingStudent && isset($incomingStudent->grade_level_id)) {
                    $grade_level_id = $incomingStudent->grade_level_id;
                }
            }
        }

        $transactionDiscount = TransactionDiscount::where('student_id', $student->id)
            ->where('school_year_id', $targetSchoolYearId)
            ->where('isSuccess', 1)
            ->sum('discount_amt');

        $paymentCategory = $this->getPaymentCategoryByGradeLevelId($grade_level_id);

        $tuition_amt = $paymentCategory?->tuitionFee?->tuition_amt ?? 0;
        $misc_amt = $paymentCategory?->miscFee?->misc_amt ?? 0;

        if ($hasTransaction) {
            $other_fee_amt = TransactionOtherFee::where('student_id', $student->id)
                ->where('school_year_id', $targetSchoolYearId)
                ->where('transaction_id', $hasTransaction->id)
                ->where('isSuccess', 1)
                ->sum('item_price');
        } else {
            $categoryLookupId = $paymentCategory?->student_category_id ?? ($paymentCategory?->id ?? 0);
            $poList = PaymentOther::with('otherFee')
                ->where('payment_category_id', $categoryLookupId)
                ->where('status', 1)
                ->get();
            $other_fee_amt = $poList->sum(fn($po) => $po->otherFee?->other_fee_amt ?? 0);
        }

        $total_fees = max(0, ($tuition_amt + $misc_amt + $other_fee_amt) - $transactionDiscount);

        $approved_payments = TransactionMonthlyPayment::where('student_id', $student->id)
            ->where('school_year_id', $targetSchoolYearId)
            ->where('isSuccess', 1)
            ->where('approval', 'Approved')
            ->sum('payment');

        $balance = max(0, $total_fees - $approved_payments);
        $status = $balance > 0 ? StatusEnum::UNPAID : StatusEnum::PAID;
        $statusBadge = $balance > 0 ? 'danger' : 'success';

        // Check if there is a pending transaction waiting for Finance approval
        $previousTransactionStatus = $this->getPendingApprovalStatus($student->id, $targetSchoolYearId);
        $isPendingApproval = $previousTransactionStatus !== null;

        return [
            'balance' => (float) $balance,
            'formatted_balance' => '₱ ' . number_format($balance, 2),
            'status' => $status,
            'statusBadge' => $statusBadge,
            'total_fees' => (float) $total_fees,
            'total_paid' => (float) $approved_payments,
            'grade_level_id' => (int) $grade_level_id,
            'school_year_id' => (int) $targetSchoolYearId,
            'previousTransactionStatus' => $previousTransactionStatus,
            'is_pending_approval' => $isPendingApproval,
            'can_submit' => !$isPendingApproval,
            'pending_approval_message' => $isPendingApproval ? self::PENDING_APPROVAL_MSG : null,
            'has_balance_prev_school_year' => $hasPreviousUnpaid,
            'has_balance_prev_school_year_message' => $hasPreviousUnpaid
                ? 'Please settle your balance from previous School Year. Thank you!'
                : null,
        ];
    }

    public function index($request)
    {
        $student = $this->getAuthorizedStudent(request('student_id'));

        if (!$student) {
            throw new Exception('Student information not found.');
        }

        $schoolYear = $this->activeSchoolYear();

        if (!$schoolYear) {
            throw new Exception('Active school year not found.');
        }

        // 1. Resolve previous active school year and previous transaction
        $previousUserID = SchoolYear::where('id', '<', $schoolYear->id)
            ->where('status', 1)
            ->max('id');

        $previousYear = $previousUserID
            ? Transaction::where('student_id', $student->id)
                ->where('school_year_id', $previousUserID)
                ->with(['paymentCategory.tuitionFee', 'paymentCategory.miscFee'])
                ->first()
            : null;

        // 2. Resolve active enrollment across all records
        $enrollment = $this->checkEnrollment($student->id);
        $currentEnrollment = $this->hasEnrollment($student->id, $schoolYear->id);

        // 3. Resolve current transaction for active school year
        $currentTransaction = $this->getTransaction($student->id, $schoolYear->id);

        $transactionStatus = ($currentEnrollment !== null)
            ? ($currentTransaction ? (int) $currentTransaction->status : 0)
            : 1;

        // 4. Initial Grade Level resolution
        $grade_level_id = $enrollment->grade_level ?? 7;

        if ($enrollment && $enrollment->grade_level <= 12) {
            if ($currentEnrollment !== null) {
                $grade_level_id = $enrollment->grade_level;
            } elseif ($previousYear && isset($previousYear->school_year_id)) {
                $prevEnrollment = $this->hasEnrollment($student->id, $previousYear->school_year_id);
                if ($prevEnrollment !== null) {
                    $grade_level_id = $prevEnrollment->grade_level + 1;
                }
            }
        }

        // 5. Check if previous year has unpaid balance (status != 0)
        $hasPreviousUnpaid = isset($previousYear->status) && (int) $previousYear->status !== 0;
        $targetSchoolYearId = $hasPreviousUnpaid ? $previousYear->school_year_id : $schoolYear->id;
        $hasTransaction = $hasPreviousUnpaid ? $previousYear : $currentTransaction;

        // 6. Refine grade level based on class details / transaction / incoming student
        if ($hasTransaction) {
            $hasSchoolYearEnrollment = $this->checkSchoolYearEnrollment($student->id, $targetSchoolYearId);

            if (isset($hasSchoolYearEnrollment->grade_level)) {
                $grade_level_id = $hasSchoolYearEnrollment->grade_level;
            } elseif (isset($hasTransaction->paymentCategory->grade_level_id)) {
                $grade_level_id = $hasTransaction->paymentCategory->grade_level_id;
            } else {
                $prevSchoolYearEnrollment = $previousUserID
                    ? $this->checkSchoolYearEnrollment($student->id, $previousUserID)
                    : null;

                if ($prevSchoolYearEnrollment && isset($prevSchoolYearEnrollment->grade_level)) {
                    $grade_level_id = $prevSchoolYearEnrollment->grade_level + $transactionStatus;
                } else {
                    $incomingStudent = IncomingStudent::where('student_id', $student->id)
                        ->where('school_year_id', $schoolYear->id)
                        ->first();
                    $grade_level_id = $incomingStudent->grade_level_id ?? $grade_level_id;
                }
            }
        } else {
            $hasSchoolYearEnrollment = $this->checkSchoolYearEnrollment($student->id, $schoolYear->id);

            if ($hasSchoolYearEnrollment && isset($hasSchoolYearEnrollment->grade_level)) {
                $grade_level_id = $hasSchoolYearEnrollment->grade_level;
            } else {
                $incomingStudent = IncomingStudent::where('student_id', $student->id)
                    ->where('school_year_id', $schoolYear->id)
                    ->first();
                if ($incomingStudent && isset($incomingStudent->grade_level_id)) {
                    $grade_level_id = $incomingStudent->grade_level_id;
                }
            }
        }

        // 7. Discounts & Transaction discounts
        $discounts = $this->getDiscounts($schoolYear, $student, $previousYear);

        $transactionDiscount = TransactionDiscount::where('student_id', $student->id)
            ->where('school_year_id', $targetSchoolYearId)
            ->where('isSuccess', 1)
            ->get()
            ->toArray();

        $transactionDiscountTotal = collect($transactionDiscount)->sum('discount_amt');

        // 8. Payment category & Tuition Details
        $paymentCategory = $this->getPaymentCategoryByGradeLevelId($grade_level_id);

        $tuition_amt = $paymentCategory?->tuitionFee?->tuition_amt ?? 0;
        $misc_amt = $paymentCategory?->miscFee?->misc_amt ?? 0;

        // Calculate other fees
        if ($hasTransaction) {
            $otherFeesCollection = TransactionOtherFee::where('student_id', $student->id)
                ->where('school_year_id', $targetSchoolYearId)
                ->where('transaction_id', $hasTransaction->id)
                ->where('isSuccess', 1)
                ->get();
            $other_fee_amt = $otherFeesCollection->sum('item_price');
        } else {
            $categoryLookupId = $paymentCategory?->student_category_id ?? ($paymentCategory?->id ?? 0);
            $poList = PaymentOther::with('otherFee')
                ->where('payment_category_id', $categoryLookupId)
                ->where('status', 1)
                ->get();
            $other_fee_amt = $poList->sum(fn($po) => $po->otherFee?->other_fee_amt ?? 0);
        }

        $sum_total_item = max(0, ($tuition_amt + $misc_amt + $other_fee_amt) - $transactionDiscountTotal);

        // 9. Approved payments & Balance calculation
        $approvedMonthlyPayments = TransactionMonthlyPayment::where('student_id', $student->id)
            ->where('school_year_id', $targetSchoolYearId)
            ->where('isSuccess', 1)
            ->where('approval', 'Approved')
            ->sum('payment');

        $balance = max(0, $sum_total_item - $approvedMonthlyPayments);
        $status = $balance > 0 ? StatusEnum::UNPAID : StatusEnum::PAID;
        $statusBadge = $balance > 0 ? 'danger' : 'success';

        // 10. Previous Year Balance Calculation
        $previousBalance = null;
        if ($previousYear) {
            $prevCat = PaymentCategory::with(['tuitionFee', 'miscFee'])
                ->where('id', $previousYear->payment_category_id)
                ->first()
                ?? PaymentCategory::with(['tuitionFee', 'miscFee'])
                ->where('grade_level_id', $previousYear->paymentCategory?->grade_level_id ?? 0)
                ->first();

            $prevTuition = $prevCat?->tuitionFee?->tuition_amt ?? 0;
            $prevMisc = $prevCat?->miscFee?->misc_amt ?? 0;

            $prevOthers = TransactionOtherFee::where('student_id', $student->id)
                ->where('school_year_id', $previousYear->school_year_id)
                ->where('transaction_id', $previousYear->id)
                ->where('isSuccess', 1)
                ->sum('item_price');

            $prevDiscounts = TransactionDiscount::where('student_id', $student->id)
                ->where('school_year_id', $previousYear->school_year_id)
                ->where('isSuccess', 1)
                ->sum('discount_amt');

            $prevTotal = max(0, ($prevTuition + $prevMisc + $prevOthers) - $prevDiscounts);

            $prevApproved = TransactionMonthlyPayment::where('student_id', $student->id)
                ->where('school_year_id', $previousYear->school_year_id)
                ->where('isSuccess', 1)
                ->where('approval', 'Approved')
                ->sum('payment');

            $previousBalance = max(0, $prevTotal - $prevApproved);
        }

        // 11. Downpayments & Payment Schemes
        $downpayments = $paymentCategory
            ? $this->getDownpayments($grade_level_id, $student->id, $targetSchoolYearId)
            : [];

        $fullPayment = self::FULL_PAYMENT;
        $fullPayment['downpayment_amt'] = $sum_total_item;
        $otherPaymentScheme = [
            $fullPayment,
            self::OTHER_PAYMENT,
        ];

        // 12. Pending approval status
        $previousTransactionStatus = $this->getPendingApprovalStatus($student->id, $targetSchoolYearId);
        $isPendingApproval = $previousTransactionStatus !== null;

        // 13. Student badge label & Unpaid previous year notice
        $studentBadgeLabel = $this->studentBadgeLabel($student, $grade_level_id, $targetSchoolYearId);
        $hasBalancePrevSchoolYear = $hasPreviousUnpaid
            ? 'Please settle your balance from previous School Year. Thank you!'
            : null;

        $studentPayment = $paymentCategory ? $this->buildStudentPayment($paymentCategory) : null;
        $transactionDetails = $paymentCategory
            ? $this->buildTransactionDetails($hasTransaction, $paymentCategory, $student->id, $targetSchoolYearId)
            : null;

        return [
            'transaction_types' => BankEnum::list(),
            'email' => Auth::user()->email ?? $student->email,
            'school_year' => $targetSchoolYearId,
            'hasTransaction' => $hasTransaction ? array_merge(
                $this->transactionToArray($hasTransaction),
                [
                    'student_payment' => $studentPayment,
                    'transaction_current_balance' => $balance,
                    'payment_cat' => $paymentCategory ? $this->buildPaymentCategory($paymentCategory) : null,
                ]
            ) : null,
            'previousUserID' => $previousUserID,
            'previousYear' => $previousYear ? array_merge(
                $this->transactionToArray($previousYear),
                [
                    'student_payment' => $paymentCategory ? $studentPayment : null,
                    'transaction_current_balance' => $previousBalance,
                    'payment_cat' => $paymentCategory ? $this->buildPaymentCategory($paymentCategory) : null,
                ]
            ) : null,
            'grade_level_id' => $grade_level_id,
            'status' => $status,
            'statusBadge' => $statusBadge,
            'transactionDiscount' => $transactionDiscount,
            'transactionDiscountTotal' => $transactionDiscountTotal,
            'transactionDetails' => $transactionDetails,
            'discounts' => $discounts,
            'paymentCategory' => $paymentCategory ? Crypt::encrypt($paymentCategory->id) : null,
            'downpayments' => $downpayments,
            'otherPayment' => $otherPaymentScheme,
            'total_fees' => $sum_total_item,
            'previousTransactionStatus' => $previousTransactionStatus,
            'is_pending_approval' => $isPendingApproval,
            'can_submit' => !$isPendingApproval,
            'pending_approval_message' => $isPendingApproval ? self::PENDING_APPROVAL_MSG : null,
            'hasBalancePrevSchoolYear' => $hasBalancePrevSchoolYear,
            'student_badge_label' => $studentBadgeLabel,
            'balance' => $balance,
        ];
    }

    public function store(array $data)
    {
        $student = $this->getAuthorizedStudent(request('student_id'));
        if (!$student) {
            throw new Exception('Student information not found.');
        }

        $schoolYearId = (int) ($data['school_year_id'] ?? $this->activeSchoolYear()->id);

        $previousTransactionStatus = $this->getPendingApprovalStatus($student->id, $schoolYearId);
        if ($previousTransactionStatus !== null) {
            return $this->error(
                self::PENDING_APPROVAL_MSG,
                Response::HTTP_BAD_REQUEST,
                []
            );
        }

        $transaction = Transaction::where('student_id', $student->id)
            ->where('school_year_id', $schoolYearId)
            ->first();

        DB::beginTransaction();
        try {
            $imageName = $this->uploadReceiptImage($data['receipt_image'] ?? null);

            if ($transaction) {
                if ($transaction->downpayment_id === null && isset($data['downpayment'])) {
                    $transaction->downpayment_id = $data['downpayment'];
                    $transaction->save();
                }

                $processData = $this->recordMonthlyPayment(
                    $data['or_no'],
                    $student->id,
                    $schoolYearId,
                    $data['payment_fee'],
                    $data['email'],
                    $data['payment_option'],
                    $transaction,
                    $imageName,
                    $data['discount'] ?? [],
                    $data['downpayment'] ?? null,
                    'update'
                );

                DB::commit();

                PaymentNotificationHelper::notifyFinanceOnPaymentRegistration(data_get($processData, 'transactionLog'));

                return $this->success(
                    'You have successfully accomplished the form. Check your email for review of Finance Dept. Thank you!',
                    Response::HTTP_OK,
                    $processData
                );
            } else {
                $enrollment = $this->checkEnrollment($student->id);
                $grade_level_id = $enrollment->grade_level ?? 7;

                $paymentCategoryId = null;
                if (!empty($data['payment_category_id'])) {
                    try {
                        $paymentCategoryId = Crypt::decrypt($data['payment_category_id']);
                    } catch (Exception $e) {
                        $paymentCategoryId = $data['payment_category_id'];
                    }
                }

                if (!$paymentCategoryId) {
                    $paymentCategory = $this->getPaymentCategoryByGradeLevelId($grade_level_id);
                    $paymentCategoryId = $paymentCategory?->id;
                }

                $newTransaction = new Transaction();
                $newTransaction->payment_category_id = $paymentCategoryId;
                $newTransaction->student_id = $student->id;
                $newTransaction->school_year_id = $schoolYearId;
                $newTransaction->downpayment_id = $data['downpayment'] ?? null;
                $newTransaction->status = 1;
                $newTransaction->save();

                // Save other fees
                $categoryLookupId = $newTransaction->paymentCategory?->student_category_id ?? $paymentCategoryId;
                $otherFees = PaymentOther::with('otherFee')
                    ->where('payment_category_id', $categoryLookupId)
                    ->where('status', 1)
                    ->get();

                if ($otherFees->isNotEmpty()) {
                    $otherFeeArray = [];
                    foreach ($otherFees as $item) {
                        $otherFeeArray[] = [
                            'transaction_id' => $newTransaction->id,
                            'student_id' => $student->id,
                            'others_fee_id' => $item->other_fee_id,
                            'school_year_id' => $schoolYearId,
                            'item_qty' => 1,
                            'item_price' => $item->otherFee?->other_fee_amt ?? 0,
                            'other_name' => $item->otherFee?->other_fee_name ?? '',
                            'isSuccess' => 1,
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                        ];
                    }
                    TransactionOtherFee::insert($otherFeeArray);
                }

                $processData = $this->recordMonthlyPayment(
                    $data['or_no'],
                    $student->id,
                    $schoolYearId,
                    $data['payment_fee'],
                    $data['email'],
                    $data['payment_option'],
                    $newTransaction,
                    $imageName,
                    $data['discount'] ?? [],
                    $data['downpayment'] ?? null,
                    'create'
                );

                DB::commit();

                PaymentNotificationHelper::notifyFinanceOnPaymentRegistration(data_get($processData, 'transactionLog'));

                return $this->success(
                    'You have successfully accomplished the form. Check your email for review of Finance Dept. Thank you!',
                    Response::HTTP_OK,
                    $processData
                );
            }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Payment registration store error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return $this->error($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function transactionLogs($request)
    {
        $student = $this->getAuthorizedStudent(request('student_id'));
        if (!$student) {
            throw new Exception('Student information not found.');
        }

        $schoolYear = $this->activeSchoolYear();
        $previousUserID = SchoolYear::where('id', '<', $schoolYear->id)->where('status', 1)->max('id');
        $previousYear = $previousUserID
            ? Transaction::where('student_id', $student->id)->where('school_year_id', $previousUserID)->first()
            : null;

        $hasPreviousUnpaid = isset($previousYear->status) && (int) $previousYear->status !== 0;
        $schoolYearId = $hasPreviousUnpaid ? $previousYear->school_year_id : $schoolYear->id;

        $queryConditions = [
            'student_id' => $student->id,
            'school_year_id' => $schoolYearId,
            'isSuccess' => 1,
        ];

        $hasMonthlyTransaction = TransactionMonthlyPayment::where($queryConditions)
            ->latest('id')
            ->first();

        $discountTotal = TransactionDiscount::where($queryConditions)->sum('discount_amt');
        $discountLists = TransactionDiscount::where($queryConditions)->get()->map(function ($q) {
            return [
                'id' => $q->id,
                'discount_type' => $q->discount_type,
                'discount_amt' => number_format($q->discount_amt, 2),
            ];
        });

        $transactionHistory = Transaction::where('student_id', $student->id)
            ->where('school_year_id', $schoolYearId)
            ->with(['paymentCategory.tuitionFee', 'paymentCategory.miscFee'])
            ->latest('id')
            ->get();

        $transactionAccount = [];
        $tuitionMiscFee = 0;
        $otherFees = [];

        if ($transactionHistory->isNotEmpty()) {
            $firstTx = $transactionHistory->first();
            $tuitionAmt = $firstTx->paymentCategory?->tuitionFee?->tuition_amt ?? 0;
            $miscAmt = $firstTx->paymentCategory?->miscFee?->misc_amt ?? 0;

            $transactionAccount = [
                'tuitionFee' => number_format($tuitionAmt, 2),
                'miscFee' => number_format($miscAmt, 2),
                'statusClass' => 'badge badge-' . ($firstTx->status == 1 ? 'danger' : 'success'),
                'statusValue' => $firstTx->status == 1 ? 'Not yet Paid' : 'Paid',
            ];

            $otherFees = TransactionOtherFee::where('student_id', $student->id)
                ->where('school_year_id', $schoolYearId)
                ->where('transaction_id', $firstTx->id)
                ->where('isSuccess', 1)
                ->get()
                ->map(function ($q) {
                    return [
                        'id' => $q->id,
                        'other_name' => $q->other_name,
                        'item_price' => number_format($q->item_price, 2),
                    ];
                });

            $otherPrice = TransactionOtherFee::where('student_id', $student->id)
                ->where('school_year_id', $schoolYearId)
                ->where('transaction_id', $firstTx->id)
                ->where('isSuccess', 1)
                ->sum('item_price');

            $tuitionMiscFee = max(0, ($tuitionAmt + $miscAmt + $otherPrice) - $discountTotal);
        }

        $transactions = TransactionMonthlyPayment::where($queryConditions)
            ->latest('id')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'payment_option' => $item->payment_option,
                    'created_at' => $item->created_at,
                    'payment' => number_format($item->payment, 2),
                    'statusClass' => 'badge badge-' . ($item->approval === 'Approved' ? 'success' : 'danger'),
                    'statusValue' => $item->approval === 'Not yet Approved' ? 'Pending' : ($item->approval ?? 'Pending'),
                    'balance' => $item->approval === 'Approved' ? '₱ ' . number_format($item->balance, 2) : 'Pending',
                    'receipt_img' => $item->receipt_img ? asset('img/receipt/' . $item->receipt_img) : null,
                ];
            });

        return [
            'discountLists' => $discountLists,
            'transactionAccount' => $transactionAccount,
            'transactions' => $transactions,
            'discount' => $discountTotal,
            'tuitionMiscFee' => number_format($tuitionMiscFee, 2),
            'hasTransaction' => $hasMonthlyTransaction,
            'otherFees' => $otherFees,
            'payment' => $hasMonthlyTransaction?->payment ?? 0,
        ];
    }

    private function recordMonthlyPayment(
        $orNumber,
        $studentId,
        $schoolYearId,
        $paymentFee,
        $email,
        $category,
        $transaction,
        $imageName,
        array $discountIds,
        $downpaymentId,
        $action
    ) {
        $tuitionAmt = $transaction->paymentCategory?->tuitionFee?->tuition_amt ?? 0;
        $miscAmt = $transaction->paymentCategory?->miscFee?->misc_amt ?? 0;
        $otherFeesAmt = TransactionOtherFee::where('student_id', $studentId)
            ->where('school_year_id', $schoolYearId)
            ->where('transaction_id', $transaction->id)
            ->where('isSuccess', 1)
            ->sum('item_price');
        $discountAmt = TransactionDiscount::where('student_id', $studentId)
            ->where('school_year_id', $schoolYearId)
            ->where('isSuccess', 1)
            ->sum('discount_amt');

        $totalFee = max(0, ($tuitionAmt + $miscAmt + $otherFeesAmt) - $discountAmt);

        $previousApprovedPayment = TransactionMonthlyPayment::where('student_id', $studentId)
            ->where('school_year_id', $schoolYearId)
            ->where('transaction_id', $transaction->id)
            ->where('isSuccess', 1)
            ->where('approval', 'Approved')
            ->sum('payment');

        $currentBalance = max(0, $totalFee - $previousApprovedPayment);
        $incomingBalance = max(0, $currentBalance - $paymentFee);

        $monthlyTransaction = new TransactionMonthlyPayment();
        $monthlyTransaction->or_no = $orNumber;
        $monthlyTransaction->transaction_id = $transaction->id;
        $monthlyTransaction->student_id = $studentId;
        $monthlyTransaction->payment = $paymentFee;
        $monthlyTransaction->school_year_id = $schoolYearId;
        $monthlyTransaction->balance = $incomingBalance;
        $monthlyTransaction->online_charges = 0.00;
        $monthlyTransaction->email = $email;
        $monthlyTransaction->number = 'NA';
        $monthlyTransaction->receipt_img = $imageName;
        $monthlyTransaction->payment_option = $category;
        $monthlyTransaction->approval = 'Not yet Approved';
        $monthlyTransaction->isSuccess = 1;

        if ($action === 'update' && $downpaymentId !== null) {
            $monthlyTransaction->downpayment_fee_id = $downpaymentId;
        }

        $monthlyTransaction->save();

        if (!empty($discountIds)) {
            $discountFeeSave = [];
            foreach ($discountIds as $id) {
                $discountFee = DiscountFee::where('id', $id)->first();
                if ($discountFee) {
                    $exists = TransactionDiscount::where('student_id', $studentId)
                        ->where('school_year_id', $schoolYearId)
                        ->where('discount_type', $discountFee->disc_type)
                        ->where('isSuccess', 1)
                        ->exists();

                    if (!$exists) {
                        $discountFeeSave[] = [
                            'student_id' => $studentId,
                            'discount_amt' => $discountFee->disc_amt,
                            'discount_type' => $discountFee->disc_type,
                            'transaction_month_paid_id' => $monthlyTransaction->id,
                            'school_year_id' => $schoolYearId,
                            'category' => $discountFee->category ?? 1,
                            'isSuccess' => 1,
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                        ];
                    }
                }
            }

            if (!empty($discountFeeSave)) {
                TransactionDiscount::insert($discountFeeSave);
            }
        }

        return [
            'transactionLog' => $monthlyTransaction,
        ];
    }

    private function uploadReceiptImage($image)
    {
        if (!$image) {
            return null;
        }

        $receiptDir = public_path('img/receipt');
        if (!File::isDirectory($receiptDir)) {
            File::makeDirectory($receiptDir, 0755, true, true);
        }

        $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
        $image->move($receiptDir, $filename);

        return $filename;
    }

    private function checkEnrollment(int $studentId)
    {
        return Enrollment::join('class_details', 'class_details.id', '=', 'enrollments.class_details_id')
            ->select([
                'class_details.id as class_detail_id',
                'class_details.school_year_id',
                'class_details.grade_level',
                'class_details.section_id',
                'class_details.status',
                'enrollments.id as enrollment_id',
                'enrollments.student_information_id',
                'enrollments.class_details_id',
            ])
            ->where('enrollments.student_information_id', $studentId)
            ->where('class_details.current', 1)
            ->where('enrollments.current', 1)
            ->where('class_details.status', 1)
            ->where('enrollments.status', 1)
            ->latest('class_details_id')
            ->orderBy('enrollments.id', 'DESC')
            ->first();
    }

    private function hasEnrollment(int $studentId, int $schoolYearId)
    {
        return Enrollment::join('class_details', 'class_details.id', '=', 'enrollments.class_details_id')
            ->select([
                'class_details.id as class_detail_id',
                'class_details.school_year_id',
                'class_details.grade_level',
                'class_details.section_id',
                'enrollments.id as enrollment_id',
                'enrollments.student_information_id',
                'enrollments.class_details_id',
            ])
            ->where('enrollments.student_information_id', $studentId)
            ->where('class_details.school_year_id', $schoolYearId)
            ->where('class_details.current', 1)
            ->where('enrollments.current', 1)
            ->where('class_details.status', 1)
            ->where('enrollments.status', 1)
            ->first();
    }

    private function checkSchoolYearEnrollment(int $studentId, int $schoolYearId)
    {
        return Enrollment::join('class_details', 'class_details.id', '=', 'enrollments.class_details_id')
            ->leftJoin('section_details', 'section_details.id', '=', 'class_details.section_id')
            ->select([
                'enrollments.id as enrollment_id',
                'enrollments.student_information_id',
                'enrollments.class_details_id',
                'class_details.school_year_id',
                'class_details.grade_level',
                'class_details.section_id',
                'section_details.section as section_name',
            ])
            ->where('enrollments.student_information_id', $studentId)
            ->where('class_details.school_year_id', $schoolYearId)
            ->where('class_details.status', 1)
            ->where('enrollments.status', 1)
            ->orderBy('enrollments.id', 'DESC')
            ->first();
    }

    private function getPendingApprovalStatus(int $studentId, int $schoolYearId): ?string
    {
        $pending = TransactionMonthlyPayment::where('student_id', $studentId)
            ->where('school_year_id', $schoolYearId)
            ->where('isSuccess', 1)
            ->whereIn('approval', ['Not yet Approved', 'Not yet approved'])
            ->latest('id')
            ->first();

        return $pending ? $pending->approval : null;
    }

    private function studentBadgeLabel($student, int $gradeLevelId, int $schoolYearId): string
    {
        $parts = [
            'LRN: ' . (Auth::user()->username ?? $student->lrn ?? ''),
            'GRADE ' . $gradeLevelId,
        ];

        try {
            $enrollment = $this->checkSchoolYearEnrollment($student->id, $schoolYearId);
            $section = $enrollment->section_name ?? null;

            if ($section) {
                $parts[] = 'SECTION ' . strtoupper($section);
            }
        } catch (\Throwable $th) {
            // Keep the badge usable even if section lookup fails.
        }

        return implode(' | ', $parts);
    }

    protected function getTransaction(int $studentId, int $schoolYearId)
    {
        return Transaction::where('student_id', $studentId)
            ->where('school_year_id', $schoolYearId)
            ->with(['paymentCategory.studentCategory', 'paymentCategory.tuitionFee', 'paymentCategory.miscFee', 'paymentCategory.otherFee'])
            ->latest()
            ->first();
    }

    protected function getPaymentCategory(int $paymentCategoryId)
    {
        return PaymentCategory::with(['studentCategory', 'tuitionFee', 'miscFee', 'otherFee'])->findOrFail($paymentCategoryId);
    }

    protected function getPaymentCategoryByGradeLevelId(int $gradeLevelId)
    {
        return PaymentCategory::where('grade_level_id', $gradeLevelId)
            ->with(['studentCategory', 'tuitionFee', 'miscFee', 'otherFee'])
            ->latest()
            ->first();
    }

    protected function buildStudentPayment($paymentCategory)
    {
        return [
            'id' => $paymentCategory->id,
            'category' => $paymentCategory->studentCategory->student_category ?? null,
            'tuition_amt' => $paymentCategory->tuitionFee->tuition_amt ?? 0,
            'misc_amt' => $paymentCategory->miscFee->misc_amt ?? 0,
        ];
    }

    protected function buildPaymentCategory($paymentCategory)
    {
        return [
            'id' => $paymentCategory->id,
            'student_category_id' => $paymentCategory->student_category_id,
            'grade_level_id' => $paymentCategory->grade_level_id,
            'tuition_fee_id' => $paymentCategory->tuition_fee_id,
            'misc_fee_id' => $paymentCategory->misc_fee_id,
            'other_fee_id' => $paymentCategory->other_fee_id,
            'months' => $paymentCategory->months,
            'current' => $paymentCategory->current,
            'status' => $paymentCategory->status,
            'created_at' => $paymentCategory->created_at,
            'updated_at' => $paymentCategory->updated_at,
            'tuition' => $paymentCategory->tuitionFee ? [
                'id' => $paymentCategory->tuitionFee->id,
                'tuition_amt' => $paymentCategory->tuitionFee->tuition_amt,
                'current' => $paymentCategory->tuitionFee->current,
                'status' => $paymentCategory->tuitionFee->status,
                'created_at' => $paymentCategory->tuitionFee->created_at,
                'updated_at' => $paymentCategory->tuitionFee->updated_at,
            ] : null,
            'misc_fee' => $paymentCategory->miscFee ? [
                'id' => $paymentCategory->miscFee->id,
                'misc_amt' => $paymentCategory->miscFee->misc_amt,
                'student_cat' => $paymentCategory->miscFee->student_cat,
                'current' => $paymentCategory->miscFee->current,
                'status' => $paymentCategory->miscFee->status,
                'created_at' => $paymentCategory->miscFee->created_at,
                'updated_at' => $paymentCategory->miscFee->updated_at,
            ] : null,
        ];
    }

    protected function buildTransactionDetails($transaction, $paymentCategory, int $studentId, int $schoolYearId)
    {
        if (!$paymentCategory) {
            return null;
        }

        if ($transaction) {
            $otherFees = TransactionOtherFee::with('otherFee')
                ->where('student_id', $studentId)
                ->where('school_year_id', $schoolYearId)
                ->where('transaction_id', $transaction->id)
                ->where('isSuccess', 1)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'transaction_id' => $item->transaction_id,
                        'or_no' => $item->or_no,
                        'student_id' => $item->student_id,
                        'others_fee_id' => $item->others_fee_id,
                        'school_year_id' => $item->school_year_id,
                        'other_name' => $item->other_name,
                        'item_qty' => $item->item_qty,
                        'item_price' => $item->item_price,
                        'isSuccess' => $item->isSuccess,
                        'created_at' => $item->created_at,
                        'updated_at' => $item->updated_at,
                        'other_fee' => $item->otherFee ? [
                            'other_fee_name' => $item->otherFee->other_fee_name,
                            'other_fee_amt' => $item->otherFee->other_fee_amt,
                        ] : null,
                    ];
                })
                ->toArray();
        } else {
            $categoryLookupId = $paymentCategory->student_category_id ?? $paymentCategory->id;
            $otherFees = PaymentOther::with('otherFee')
                ->where('payment_category_id', $categoryLookupId)
                ->where('status', 1)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'others_fee_id' => $item->other_fee_id,
                        'other_name' => $item->otherFee?->other_fee_name,
                        'item_price' => $item->otherFee?->other_fee_amt,
                        'other_fee' => $item->otherFee ? [
                            'other_fee_name' => $item->otherFee->other_fee_name,
                            'other_fee_amt' => $item->otherFee->other_fee_amt,
                        ] : null,
                    ];
                })
                ->toArray();
        }

        return [
            'id' => $paymentCategory->id,
            'student_category_id' => $paymentCategory->student_category_id,
            'grade_level_id' => $paymentCategory->grade_level_id,
            'tuition_fee_id' => $paymentCategory->tuition_fee_id,
            'misc_fee_id' => $paymentCategory->misc_fee_id,
            'other_fee_id' => $paymentCategory->other_fee_id,
            'months' => $paymentCategory->months,
            'current' => $paymentCategory->current,
            'status' => $paymentCategory->status,
            'created_at' => $paymentCategory->created_at,
            'updated_at' => $paymentCategory->updated_at,
            'tuition' => $paymentCategory->tuitionFee ? [
                'id' => $paymentCategory->tuitionFee->id,
                'tuition_amt' => $paymentCategory->tuitionFee->tuition_amt,
                'current' => $paymentCategory->tuitionFee->current,
                'status' => $paymentCategory->tuitionFee->status,
                'created_at' => $paymentCategory->tuitionFee->created_at,
                'updated_at' => $paymentCategory->tuitionFee->updated_at,
            ] : null,
            'misc_fee' => $paymentCategory->miscFee ? [
                'id' => $paymentCategory->miscFee->id,
                'misc_amt' => $paymentCategory->miscFee->misc_amt,
                'student_cat' => $paymentCategory->miscFee->student_cat,
                'current' => $paymentCategory->miscFee->current,
                'status' => $paymentCategory->miscFee->status,
                'created_at' => $paymentCategory->miscFee->created_at,
                'updated_at' => $paymentCategory->miscFee->updated_at,
            ] : null,
            'other_fees' => $otherFees,
        ];
    }

    protected function getDiscounts($schoolYear, $studentInformation, $previousYear)
    {
        $discount = DiscountFee::whereStatus(1)->whereCurrent(1)->whereApplyTo(1)->get();

        foreach ($discount as $key => $data) {
            $discount[$key]['school_year_id'] = $schoolYear->id;
            $discount[$key]['student_information_id'] = $studentInformation->id;
            $discount[$key]['prev_sy_id'] = $previousYear;
        }

        return new CheckDiscountResource($discount);
    }

    protected function getDownpayments(int $gradeLevelId, int $studentId, int $schoolYearId)
    {
        return DownpaymentFee::where('grade_level_id', $gradeLevelId)
            ->where('current', 1)
            ->where('status', 1)
            ->get()
            ->map(function ($item) use ($studentId, $schoolYearId) {
                $selected = Transaction::where('student_id', $studentId)
                    ->where('school_year_id', $schoolYearId)
                    ->where('downpayment_id', $item->id)
                    ->first();

                return [
                    'id' => $item->id,
                    'downpayment_amt' => $item->downpayment_amt,
                    'grade_level_id' => $item->grade_level_id,
                    'modified' => $item->modified,
                    'selected' => (bool) $selected,
                ];
            })
            ->toArray();
    }

    protected function transactionToArray($transaction)
    {
        return [
            'id' => $transaction->id,
            'or_no' => $transaction->or_no,
            'payment_category_id' => $transaction->payment_category_id,
            'student_id' => $transaction->student_id,
            'school_year_id' => $transaction->school_year_id,
            'downpayment_id' => $transaction->downpayment_id,
            'isEnrolled' => $transaction->isEnrolled,
            'status' => $transaction->status,
            'created_at' => $transaction->created_at,
            'updated_at' => $transaction->updated_at,
        ];
    }
}
