<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StudentPaymentRegisteredNotification extends Notification
{
    use Queueable;

    public $monthlyTransaction;
    public string $studentName;

    /**
     * Create a new notification instance.
     *
     * @param mixed $monthlyTransaction
     * @param string $studentName
     * @return void
     */
    public function __construct($monthlyTransaction, string $studentName = '')
    {
        $this->monthlyTransaction = $monthlyTransaction;
        $this->studentName = $studentName;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable): array
    {
        $amount = number_format((float) ($this->monthlyTransaction->payment ?? 0), 2);
        $orNo = $this->monthlyTransaction->or_no ?? 'N/A';
        $studentName = $this->studentName ?: 'A student';

        return [
            'title' => 'Payment Notification',
            'message' => "Payment registration submitted by student: {$studentName} (OR# {$orNo}) for ₱{$amount}.",
            'transaction_month_paid_id' => $this->monthlyTransaction->id ?? null,
            'student_id' => $this->monthlyTransaction->student_id ?? null,
            'student_name' => $studentName,
            'amount' => $this->monthlyTransaction->payment ?? 0,
            'or_no' => $orNo,
            'type' => 'student_payment_registration',
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
