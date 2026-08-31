<?php

namespace App\Helpers;

use App\Models\StudentInformation;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\StudentPaymentRegisteredNotification;
use App\Services\OneSignalService;
use Illuminate\Support\Facades\Log;
use Throwable;

class PaymentNotificationHelper
{
    /**
     * Notify all finance role users when a student registers a payment.
     *
     * @param mixed $monthlyTransaction
     * @return void
     */
    public static function notifyFinanceOnPaymentRegistration($monthlyTransaction): void
    {
        if (!$monthlyTransaction) {
            return;
        }

        try {
            $studentName = '';
            if (isset($monthlyTransaction->student_id)) {
                $studentInfo = StudentInformation::find($monthlyTransaction->student_id);
                if ($studentInfo) {
                    $studentName = trim(($studentInfo->first_name ?? '') . ' ' . ($studentInfo->last_name ?? ''));
                }
            }

            // 1. Get all active Finance role users (role = 6, status = 1)
            $financeUsers = User::where('role', 6)
                ->where('status', 1)
                ->get();

            if ($financeUsers->isEmpty()) {
                return;
            }

            // 2. Save database notification for each finance user
            foreach ($financeUsers as $financeUser) {
                try {
                    $financeUser->notify(new StudentPaymentRegisteredNotification($monthlyTransaction, $studentName));
                } catch (Throwable $dbNotifException) {
                    Log::warning('Database notification failed for finance user ID ' . $financeUser->id . ': ' . $dbNotifException->getMessage());
                }
            }

            // 3. Send OneSignal Push Notification to all Finance users
            $financeUserIds = $financeUsers->pluck('id')->all();
            $playerIds = Subscription::where('subscribable_type', User::class)
                ->whereIn('subscribable_id', $financeUserIds)
                ->pluck('player_id')
                ->filter()
                ->unique()
                ->values()
                ->all();

            if (!empty($playerIds)) {
                $amount = number_format((float) ($monthlyTransaction->payment ?? 0), 2);
                $orNo = $monthlyTransaction->or_no ?? 'N/A';
                $displayName = $studentName ?: 'A student';
                $pushMessage = "Payment Notification:\nPayment registration submitted by student: {$displayName} (OR# {$orNo}) for ₱{$amount}.";

                OneSignalService::sendPush([
                    'include_player_ids' => $playerIds,
                    'headings' => ['en' => 'Payment Notification'],
                ], $pushMessage);
            }
        } catch (Throwable $e) {
            Log::warning('Error in notifyFinanceOnPaymentRegistration: ' . $e->getMessage());
        }
    }
}
