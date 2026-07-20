<?php

namespace App\Services;

use App\Enums\BankEnum;
use App\Enums\StatusEnum;
use App\Http\Resources\CheckDiscountResource;
use App\Models\{StudentInformation, Transaction, PaymentCategory, DownpaymentFee, TransactionMonthlyPayment};
use App\Models\DiscountFee;
use App\Models\PaymentOther;
use App\Models\TransactionDiscount;
use App\Models\TransactionOtherFee;
use App\Traits\SchoolYear;
use Exception;
use Illuminate\Support\Facades\Auth;

class PaymentService
{
    use SchoolYear;

    protected $students;

    public function __construct(StudentInformation $students)
    {
        $this->students = $students;
    }

    public function index($request)
    {
        $student = $this->students->where('user_id', Auth::user()->id)->first();
        
        if (!$student) {
            throw new Exception('Student student not found.');
        }

        $schoolYear = $this->activeSchoolYear();

        if (!$schoolYear) {
            throw new Exception('Active school year not found.');
        }

        $previousYear = $schoolYear->id - 1;

        $currentTransaction = $this->getTransaction($student->id, $schoolYear->id);
        
        $previousTransaction = $this->getTransaction($student->id, $previousYear);

        $paymentCategory = $currentTransaction?->paymentCategory;
        
        if (!$currentTransaction && $previousTransaction) {
            $paymentCategoryId = $previousTransaction->payment_category_id;
            $gradeLevelId = $this->getPaymentCategory($paymentCategoryId)->grade_level_id;
            $paymentCategory = $this->getPaymentCategoryByGradeLevelId($gradeLevelId+1);
        }
       
        $studentPayment = $paymentCategory ? $this->buildStudentPayment($paymentCategory) : null;

        $transactionDetails = $paymentCategory ? $this->buildTransactionDetails($currentTransaction, $paymentCategory) : null;

        $discounts = $this->getDiscounts($schoolYear, $student, $previousTransaction);

        $downpayments = $paymentCategory ? $this->getDownpayments($paymentCategory->grade_level_id, $currentTransaction?->downpayment_id) : [];

        $otherPayment = $paymentCategory ? $this->getOtherPaymentOptions($paymentCategory->id) : [];

        $transactionDiscount = $currentTransaction ? TransactionDiscount::where('student_id', $currentTransaction->student_id)
            ->where('school_year_id', $currentTransaction->school_year_id)
            ->where('isSuccess', 1)
            ->get()
            ->toArray() : [];

        $transactionDiscountTotal = collect($transactionDiscount)->sum('discount_amt');
        
        $balance = $this->getCurrentTransactionBalance($currentTransaction);

        $status = $balance > 0 ? StatusEnum::UNPAID : StatusEnum::PAID;

        $previousBalance = $this->getCurrentTransactionBalance($previousTransaction);

        return [
            'transaction_types' => BankEnum::list(),
            'email' => Auth::user()->email,
            'school_year' => $schoolYear->id,
            'hasTransaction' => $currentTransaction ? array_merge(
                $this->transactionToArray($currentTransaction),
                [
                    'student_payment' => $studentPayment,
                    'transaction_current_balance' => $balance,
                    'payment_cat' => $paymentCategory ? $this->buildPaymentCategory($paymentCategory) : null,
                ]
            ) : null,
            'previousUserID' => $previousTransaction?->id,
            'previousYear' => $previousTransaction ? array_merge(
                $this->transactionToArray($previousTransaction),
                [
                    'student_payment' => $paymentCategory ? $studentPayment : null,
                    'transaction_current_balance' => $previousBalance,
                    'payment_cat' => $paymentCategory ? $this->buildPaymentCategory($paymentCategory) : null,
                ]
            ) : null,
            'grade_level_id' => $paymentCategory?->grade_level_id,
            'status' => $status,
            'statusBadge' => $this->getStatusBadge($status),
            'transactionDiscount' => $transactionDiscount,
            'transactionDiscountTotal' => $transactionDiscountTotal,
            'transactionDetails' => $transactionDetails,
            'discounts' => $discounts,
            'paymentCategory' => $paymentCategory ? encrypt($paymentCategory->id) : null,
            'downpayments' => $downpayments,
            'otherPayment' => $otherPayment,
            'total_fees' => $this->calculateTotalFees($paymentCategory, $currentTransaction, $downpayments),
            'previousTransactionStatus' => $previousTransaction ? ($previousBalance > 0 ? StatusEnum::UNPAID : StatusEnum::PAID) : null,
            'hasBalancePrevSchoolYear' => $previousBalance === null ? null : ($previousBalance > 0 ? 1 : 0),
            'balance' => $balance,
        ];
    }

    private function enrollment($studentInformationId, $currentSy)
    {
        return Enrollment::join('class_details', 'class_details.id', '=', 'enrollments.class_details_id')
            ->select(
                DB::raw("
                            class_details.id,
                            class_details.school_year_id,
                            class_details.grade_level,
                            class_details.section_id,
                            class_details.status,
                            enrollments.id,
                            enrollments.id,
                            enrollments.student_information_id,
                            enrollments.class_details_id
                        ")
            )
            ->where('enrollments.student_information_id', $studentInformationId)
            ->when($currentSy, function ($e) use ($currentSy) {
                $e->where('class_details.school_year_id', $currentSy);
            })
            ->where('class_details.current', 1)
            ->where('enrollments.current', 1)
            ->where('class_details.status', 1)
            ->where('enrollments.status', 1)
            ->latest('class_details_id')
            ->orderBy('enrollments.id', 'DESC')
            ->first();
    }

    protected function getTransaction(int $studentId, int $schoolYearId)
    {
        return Transaction::where('student_id', $studentId)
            ->where('school_year_id', $schoolYearId)
            ->with('paymentCategory.studentCategory', 'paymentCategory.tuitionFee', 'paymentCategory.miscFee', 'paymentCategory.otherFee')
            ->latest()
            ->first();
    }

    protected function getPaymentCategory(int $paymentCategoryId)
    {
        return PaymentCategory::with('studentCategory', 'tuitionFee', 'miscFee', 'otherFee')->findOrFail($paymentCategoryId);
    }

    protected function getPaymentCategoryByGradeLevelId(int $gradeLevelId)
    {
        return PaymentCategory::where('grade_level_id', $gradeLevelId)
            ->with('studentCategory', 'tuitionFee', 'miscFee', 'otherFee')
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

    protected function buildTransactionDetails($transaction, $paymentCategory)
    {
        if (!$paymentCategory) {
            return null;
        }

        // Load other_fees relationship if transaction exists
        if ($transaction) {
            $otherFees = $this->getTransactionOtherFees($transaction->id);
        } else {
            $otherFees = [];
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

    protected function getTransactionOtherFees(int $transactionId)
    {
        return TransactionOtherFee::with('otherFee')
            ->where('transaction_id', $transactionId)
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
    }

    protected function getDiscountFeesCollection(int $studentId, int $schoolYearId): float
    {
        return TransactionDiscount::where('student_id', $studentId)
            ->where('school_year_id', $schoolYearId)
            ->where('isSuccess', 1)
            ->sum('discount_amt');
    }

    public function getTransactionDiscount($request)
    {
        return TransactionDiscount::when(
            $request,
            function ($item) use ($request) {
                $item->where('student_id', $request['student_information_id'])
                    ->where('school_year_id', $request['school_year_id'])
                    ->where('isSuccess', 1);
            }
        );
    }

    protected function getDiscountFees($request = null)
    {
        return TransactionDiscount::when(
            $request,
            function ($query) use ($request) {
                $query->where('student_id', $request['student_information_id'] ?? null)
                    ->where('school_year_id', $request['school_year_id'] ?? null)
                    ->where('isSuccess', 1);
            }
        );
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

    protected function getDownpayments(int $gradeLevelId, $selectedDownpaymentId = null)
    {
        return DownpaymentFee::where('grade_level_id', $gradeLevelId)
            ->where('current', 1)
            ->where('status', 1)
            ->get()
            ->map(function ($item) use ($selectedDownpaymentId) {
                return [
                    'id' => $item->id,
                    'downpayment_amt' => $item->downpayment_amt,
                    'grade_level_id' => $item->grade_level_id,
                    'modified' => $item->modified,
                    'selected' => $selectedDownpaymentId === $item->id,
                ];
            })
            ->toArray();
    }

    protected function getOtherPaymentOptions(int $paymentCategoryId)
    {
        return PaymentOther::with('otherFee')
            ->where('payment_category_id', $paymentCategoryId)
            ->where('status', 1)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'description' => $item->otherFee?->other_fee_name,
                    'downpayment_amt' => $item->otherFee?->other_fee_amt,
                ];
            })
            ->toArray();
    }

    protected function getTransactionDiscounts($transaction)
    {
        if (! $transaction) {
            return [];
        }

        return TransactionDiscount::where('student_id', $transaction->student_id)
            ->where('school_year_id', $transaction->school_year_id)
            ->where('isSuccess', 1)
            ->get()
            ->toArray();
    }

    protected function getCurrentTransactionBalance($transaction)
    {
        if (!$transaction) {
            return 0;
        }

        $monthly = TransactionMonthlyPayment::where('transaction_id', $transaction->id)
            ->latest()
            ->first();

        return $monthly ? (float) $monthly->balance : 0;
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

    protected function getStatusBadge(string $status)
    {
        return $status === 'PAID' ? 'success' : 'danger';
    }

    protected function calculateTotalFees($paymentCategory, $transaction, array $downpayments)
    {
        if ($transaction && $transaction->downpayment_id) {
            $selected = collect($downpayments)->firstWhere('id', $transaction->downpayment_id);
            if ($selected) {
                return $selected['downpayment_amt'];
            }
        }

        if ($paymentCategory) {
            $total = ($paymentCategory->tuitionFee?->tuition_amt ?? 0) +
                ($paymentCategory->miscFee?->misc_amt ?? 0);
            return $total;
        }

        return 0;
    }
}
