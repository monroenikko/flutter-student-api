<?php

namespace App\Services;

use App\Http\Resources\ClassScheduleResource;
use App\Models\Enrollment;
use App\Models\SchoolYear as SchoolYearModel;
use App\Models\StudentInformation;
use App\Traits\ResponseApi;
use App\Traits\SchoolYear;
use App\Traits\HasSiblingAccess;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class ClassScheduleService
{
    use ResponseApi, SchoolYear, HasSiblingAccess;

    public function getStudentClassSchedule(Request $request)
    {
        try {
            $student = $this->student();

            if (! $student) {
                return $this->error('Student information not found.', Response::HTTP_NOT_FOUND);
            }

            $schoolYearId = $request->get('school_year_id');

            if ($schoolYearId) {
                $schoolYear = SchoolYearModel::find($schoolYearId);
                if (! $schoolYear) {
                    return $this->error('School year not found.', Response::HTTP_NOT_FOUND);
                }
                $schoolYearId = $schoolYear->id;
            } else {
                $activeSy = $this->activeSchoolYear();
                $schoolYearId = $activeSy?->id;
            }

            // Find enrollment for the requested school year or fallback
            $enrollment = $this->getStudentEnrollment($student->id, $schoolYearId);

            if (! $enrollment && ! $request->get('school_year_id') && $schoolYearId) {
                // Fallback to previous school year if active year enrollment isn't found
                $enrollment = $this->getStudentEnrollment($student->id, $schoolYearId - 1);
            }

            if (! $enrollment || ! $enrollment->classDetail) {
                return $this->error('No class enrollment found for the selected school year.', Response::HTTP_NOT_FOUND);
            }

            $classDetail = $enrollment->classDetail;
            $subjectDetails = $classDetail->classSubjectDetails ?? collect();

            $parsedData = $this->parseScheduleByDays($classDetail, $subjectDetails);

            return $this->success(
                'Class schedule successfully fetched.',
                Response::HTTP_OK,
                new ClassScheduleResource($parsedData)
            );
        } catch (Exception $e) {
            return $this->error('Failed to retrieve class schedule: ' . $e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    private function getStudentEnrollment(int $studentId, ?int $schoolYearId)
    {
        return Enrollment::with([
            'classDetail.section',
            'classDetail.adviser',
            'classDetail.room',
            'classDetail.schoolYear',
            'classDetail.classSubjectDetails' => function ($query) {
                $query->where('status', '!=', 0)->orderBy('class_time_from', 'ASC');
            },
            'classDetail.classSubjectDetails.subjectDetails',
            'classDetail.classSubjectDetails.assignFaculty',
            'classDetail.classSubjectDetails.room',
        ])
            ->where('student_information_id', $studentId)
            ->where('status', 1)
            ->when($schoolYearId, function ($q) use ($schoolYearId) {
                $q->whereHas('classDetail', function ($sq) use ($schoolYearId) {
                    $sq->where('school_year_id', $schoolYearId);
                });
            })
            ->first();
    }

    private function parseScheduleByDays($classDetail, $subjectDetails): array
    {
        $dayMap = [
            '1' => 'Mon', 'm' => 'Mon', 'mon' => 'Mon', 'monday' => 'Mon',
            '2' => 'Tue', 't' => 'Tue', 'tue' => 'Tue', 'tuesday' => 'Tue',
            '3' => 'Wed', 'w' => 'Wed', 'wed' => 'Wed', 'wednesday' => 'Wed',
            '4' => 'Thu', 'th' => 'Thu', 'thu' => 'Thu', 'thursday' => 'Thu',
            '5' => 'Fri', 'f' => 'Fri', 'fri' => 'Fri', 'friday' => 'Fri',
            '6' => 'Sat', 'sat' => 'Sat', 'saturday' => 'Sat',
            '7' => 'Sun', 'sun' => 'Sun', 'sunday' => 'Sun',
        ];

        $defaultDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'];
        $schedulesByDay = [
            'Mon' => [],
            'Tue' => [],
            'Wed' => [],
            'Thu' => [],
            'Fri' => [],
        ];

        $rawSchedules = [];

        foreach ($subjectDetails as $subjectDetail) {
            $daysMatched = $this->extractDays($subjectDetail, $dayMap);

            $timeFromRaw = $subjectDetail->class_time_from;
            $timeToRaw = $subjectDetail->class_time_to;

            $timeFromFormatted = $timeFromRaw ? date('H:i', strtotime($timeFromRaw)) : '';
            $timeToFormatted = $timeToRaw ? date('H:i', strtotime($timeToRaw)) : '';
            $timeDisplay = ($timeFromFormatted && $timeToFormatted) ? "{$timeFromFormatted} - {$timeToFormatted}" : '';

            $faculty = $subjectDetail->assignFaculty;
            $facultyName = '-';
            if ($faculty) {
                $facultyName = trim(($faculty->first_name ?? '') . ' ' . ($faculty->last_name ?? ''));
                if (empty($facultyName)) {
                    $facultyName = $faculty->full_name ?? '-';
                }
            }

            $roomName = $subjectDetail->room?->room_code
                ?? $subjectDetail->room?->room_description
                ?? $classDetail->room?->room_code
                ?? $classDetail->room?->room_description
                ?? 'Room N/A';

            $item = [
                'id'                      => $subjectDetail->id,
                'class_subject_detail_id' => $subjectDetail->id,
                'subject_id'              => $subjectDetail->subject_id,
                'subject_code'            => $subjectDetail->subjectDetails?->subject_code ?? '',
                'subject'                 => $subjectDetail->subjectDetails?->subject ?? 'Unassigned Subject',
                'faculty_name'            => $facultyName,
                'room'                    => $roomName,
                'time_from'               => $timeFromFormatted,
                'time_to'                 => $timeToFormatted,
                'time_display'            => $timeDisplay,
                'class_days'              => $subjectDetail->class_days,
                'class_schedule'          => $subjectDetail->class_schedule,
            ];

            $rawSchedules[] = $item;

            foreach ($daysMatched as $dayAbbr) {
                if (! isset($schedulesByDay[$dayAbbr])) {
                    $schedulesByDay[$dayAbbr] = [];
                    if (! in_array($dayAbbr, $defaultDays)) {
                        $defaultDays[] = $dayAbbr;
                    }
                }
                $schedulesByDay[$dayAbbr][] = $item;
            }
        }

        // Sort items in each day by start time
        foreach ($schedulesByDay as $day => &$items) {
            usort($items, function ($a, $b) {
                return strcmp($a['time_from'] ?? '', $b['time_from'] ?? '');
            });
        }

        return [
            'class_detail'     => $classDetail,
            'days'             => $defaultDays,
            'schedules_by_day' => $schedulesByDay,
            'raw_schedules'    => $rawSchedules,
        ];
    }

    private function extractDays($subjectDetail, array $dayMap): array
    {
        $days = [];

        // 1. Try parsing class_schedule format (e.g. Mon@07:30-08:30;Tue@07:30-08:30 or 1@7:30-8:30)
        if (! empty($subjectDetail->class_schedule)) {
            $rawEntries = explode(';', rtrim(str_replace('/', ';', $subjectDetail->class_schedule), ';'));
            foreach ($rawEntries as $entry) {
                if (str_contains($entry, '@')) {
                    $parts = explode('@', $entry);
                    $dayRaw = strtolower(trim($parts[0]));
                    if (isset($dayMap[$dayRaw]) && ! in_array($dayMap[$dayRaw], $days)) {
                        $days[] = $dayMap[$dayRaw];
                    }
                }
            }
        }

        // 2. Try parsing class_days if empty from class_schedule (e.g. M,T,W,TH,F or 1,2,3,4,5 or M-F)
        if (empty($days) && ! empty($subjectDetail->class_days)) {
            $rawDays = str_replace([' ', ';', '/'], ',', $subjectDetail->class_days);
            $parts = explode(',', $rawDays);
            foreach ($parts as $p) {
                $pClean = strtolower(trim($p));
                if (isset($dayMap[$pClean]) && ! in_array($dayMap[$pClean], $days)) {
                    $days[] = $dayMap[$pClean];
                }
            }

            // Handle range like M-F or Mon-Fri
            if (empty($days) && str_contains($subjectDetail->class_days, '-')) {
                $range = explode('-', $subjectDetail->class_days);
                if (count($range) === 2) {
                    $start = strtolower(trim($range[0]));
                    $end = strtolower(trim($range[1]));
                    $startDay = $dayMap[$start] ?? null;
                    $endDay = $dayMap[$end] ?? null;
                    $orderedDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                    if ($startDay && $endDay) {
                        $startIdx = array_search($startDay, $orderedDays);
                        $endIdx = array_search($endDay, $orderedDays);
                        if ($startIdx !== false && $endIdx !== false && $startIdx <= $endIdx) {
                            for ($i = $startIdx; $i <= $endIdx; $i++) {
                                $days[] = $orderedDays[$i];
                            }
                        }
                    }
                }
            }
        }

        // If still no day specified, default to Mon-Fri
        if (empty($days)) {
            $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'];
        }

        return $days;
    }

    private function student()
    {
        return $this->getAuthorizedStudent(request('student_id'));
    }
}
