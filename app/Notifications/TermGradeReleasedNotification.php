<?php

namespace App\Notifications;

use App\Models\GradeReleaseSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TermGradeReleasedNotification extends Notification
{
    use Queueable;

    public $schedule;
    public $termName;
    public $schoolYearName;

    /**
     * Create a new notification instance.
     *
     * @param GradeReleaseSchedule $schedule
     * @param string|null $schoolYearName
     * @return void
     */
    public function __construct(GradeReleaseSchedule $schedule, $schoolYearName = null)
    {
        $this->schedule = $schedule;

        $terms = [
            1 => '1st Term',
            2 => '2nd Term',
            3 => '3rd Term',
            4 => '4th Term',
        ];
        $this->termName = $terms[$schedule->term] ?? ($schedule->term . ' Term');
        $this->schoolYearName = $schoolYearName ?: (optional($schedule->schoolYear)->school_year ?? '');
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        $sySuffix = $this->schoolYearName ? " (S.Y. {$this->schoolYearName})" : '';

        return [
            'title'          => "{$this->termName} Grades Available",
            'message'        => "Your grades for {$this->termName}{$sySuffix} have been released. You can now view your grade sheet.",
            'term'           => $this->schedule->term,
            'term_type'      => $this->schedule->term_type,
            'school_year_id' => $this->schedule->school_year_id,
            'type'           => 'grade_release',
            'created_at'     => now()->toDateTimeString(),
        ];
    }
}
