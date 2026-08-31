<?php

namespace App\Http\Resources;

use App\Traits\FormatsSubjectTitle;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class GradeSheetResource extends JsonResource
{
    use FormatsSubjectTitle;

    protected $releaseSchedules;

    public function __construct($resource, $releaseSchedules = null)
    {
        parent::__construct($resource);
        $this->releaseSchedules = $releaseSchedules;
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $termType = $this['classDetail']['term_type'] ?? $this->classDetail->term_type ?? 'old';
        $isNewTerm = $termType === 'new';

        $schedules = $this->releaseSchedules ?? $this['grade_release_schedules'] ?? $this->grade_release_schedules ?? collect();
        $formattedSchedules = self::formatSchedules($schedules);
        $hasSchedules = !empty($formattedSchedules);

        $isTermRevealed = function (int $term) use ($formattedSchedules, $hasSchedules) {
            if (!$hasSchedules) {
                return true;
            }
            foreach ($formattedSchedules as $sched) {
                if ((int) $sched['term'] === $term) {
                    return (bool) $sched['is_revealed'];
                }
            }
            return false;
        };

        return [
            'section' => $this['classDetail']['section']['section'] ?? $this->classDetail->section->section ?? 'none',
            'grade_level' => $this['classDetail']['grade_level'] ?? $this->classDetail->grade_level ?? 'none',
            'term_type' => $termType,
            'adviser' => $this['classDetail']['adviser']['full_name'] ?? $this->classDetail->adviser->full_name ?? 'none',
            'grade_release_schedules' => $formattedSchedules,
            'grades' => $this['studentEnrolledSubjects']->map(function ($item) use ($isNewTerm, $isTermRevealed) {
                $rawSubject = $item['subSubject']['sub_subject'] ?? $item->subSubject->sub_subject ?? $item['classSubjectDetails']['subjectDetails']['subject'] ?? $item['subjectDetails']['subject'] ?? '';
                $subjectName = $this->formatSubjectTitle($rawSubject);
                $subjectCode = $item['subSubject']['sub_subject_code'] ?? $item->subSubject->sub_subject_code ?? $item['classSubjectDetails']['subjectDetails']['subject_code'] ?? $item['subjectDetails']['subject_code'] ?? '';

                $hasSubSubjectId = !empty($item['sub_subject_id']) || !empty($item->sub_subject_id) || !empty($item['subSubject']) || !empty($item->subSubject);
                $isKnownSubComponent = (
                    strripos($subjectName, 'Music') !== false ||
                    strripos($subjectName, 'Arts') !== false ||
                    strripos($subjectName, 'Physical') !== false ||
                    strripos($subjectName, 'Health') !== false
                );
                $isSubSubject = $hasSubSubjectId || $isKnownSubComponent;

                $categoryCode = $item['classSubjectDetails']['subjectCategory']['code'] 
                    ?? $item['subjectDetails']['subjectCategory']['code'] 
                    ?? $item->classSubjectDetails->subjectCategory->code 
                    ?? $item->subjectDetails->subjectCategory->code 
                    ?? 'core';
                $isElective = strtolower((string) $categoryCode) === 'elective';
                $subjectSem = (int) ($item['classSubjectDetails']['sem'] ?? $item['sem'] ?? $item->classSubjectDetails->sem ?? $item->sem ?? 1);

                $firG = $isTermRevealed(1) ? (int) ($item['fir_g'] ?? 0) : 0;
                $secG = $isTermRevealed(2) ? (int) ($item['sec_g'] ?? 0) : 0;
                $thiG = $isTermRevealed(3) ? (int) ($item['thi_g'] ?? 0) : 0;

                $gradeData = [
                    'subject' => $subjectName,
                    'subject_code' => $subjectCode,
                    'is_sub_subject' => $isSubSubject,
                    'is_elective' => $isElective,
                    'term' => $subjectSem,
                    'fir_g' => $firG,
                    'sec_g' => $secG,
                    'thi_g' => $thiG,
                ];

                if (!$isNewTerm) {
                    $gradeData['fou_g'] = $isTermRevealed(4) ? (int) ($item['fou_g'] ?? 0) : 0;
                }

                $gradeItemForFinal = [
                    'fir_g' => $firG,
                    'sec_g' => $secG,
                    'thi_g' => $thiG,
                    'fou_g' => $gradeData['fou_g'] ?? 0,
                ];

                $finalGradeVal = $this->finalGrade($gradeItemForFinal, $isNewTerm, $isElective, $subjectSem);
                $gradeData['final_g'] = ($finalGradeVal > 0) ? round($finalGradeVal) : '';
                $gradeData['faculty'] = $item['classSubjectDetails']['assignFaculty']['full_name'] ?? $item['classSubjectDetails']['teacherSubject']['assignFaculty']['full_name'] ?? 'TBA';
                $gradeData['order'] = $item['classSubjectDetails']['class_subject_order'] ?? 0;
                $gradeData['status'] = $item['status'] ?? 0;

                return $gradeData;
            })
                ->sortBy('order')
                ->values()
                ->toArray()
        ];
    }

    public static function formatSchedules($schedules): array
    {
        if (empty($schedules)) {
            return [];
        }

        return collect($schedules)->map(function ($schedule) {
            $isEnabled = is_array($schedule) ? (int) ($schedule['is_enabled'] ?? 0) : (int) $schedule->is_enabled;
            $status = is_array($schedule) ? ($schedule['status'] ?? 'draft') : $schedule->status;
            $isRevealed = $isEnabled === 1 && strtolower((string) $status) === 'published';
            $publishedAt = is_array($schedule) ? ($schedule['published_at'] ?? null) : $schedule->published_at;
            $scheduledAt = is_array($schedule) ? ($schedule['scheduled_at'] ?? null) : $schedule->scheduled_at;

            return [
                'id' => is_array($schedule) ? ($schedule['id'] ?? null) : $schedule->id,
                'school_year_id' => is_array($schedule) ? (int) ($schedule['school_year_id'] ?? 0) : (int) $schedule->school_year_id,
                'term_type' => is_array($schedule) ? ($schedule['term_type'] ?? 'new') : $schedule->term_type,
                'term' => is_array($schedule) ? (int) ($schedule['term'] ?? 1) : (int) $schedule->term,
                'is_enabled' => $isEnabled,
                'status' => $status,
                'is_revealed' => $isRevealed,
                'is_locked' => !$isRevealed,
                'scheduled_at' => $scheduledAt ? Carbon::parse($scheduledAt)->format('Y-m-d H:i:s') : null,
                'published_at' => $publishedAt ? Carbon::parse($publishedAt)->format('Y-m-d H:i:s') : null,
            ];
        })->values()->toArray();
    }

    private function finalGrade($item, bool $isNewTerm = false, bool $isElective = false, int $subjectSem = 1)
    {
        $first = (isset($item['fir_g']) && $item['fir_g'] > 0) ? (float) $item['fir_g'] : ((isset($item->fir_g) && $item->fir_g > 0) ? (float) $item->fir_g : 0);
        $second = (isset($item['sec_g']) && $item['sec_g'] > 0) ? (float) $item['sec_g'] : ((isset($item->sec_g) && $item->sec_g > 0) ? (float) $item->sec_g : 0);
        $third = (isset($item['thi_g']) && $item['thi_g'] > 0) ? (float) $item['thi_g'] : ((isset($item->thi_g) && $item->thi_g > 0) ? (float) $item->thi_g : 0);

        if ($isNewTerm) {
            if ($isElective) {
                if ($subjectSem === 1 && $first > 0) {
                    return $first;
                } elseif ($subjectSem === 2 && $second > 0) {
                    return $second;
                } elseif ($subjectSem === 3 && $third > 0) {
                    return $third;
                }
                return 0;
            }

            $sum = $first + $second + $third;
            $divisor = ($first > 0 ? 1 : 0) + ($second > 0 ? 1 : 0) + ($third > 0 ? 1 : 0);

            if ($first != 0 && $second != 0 && $third != 0 && $divisor != 0) {
                return $sum / $divisor;
            }

            return 0;
        }

        $fourth = (isset($item['fou_g']) && $item['fou_g'] > 0) ? (float) $item['fou_g'] : ((isset($item->fou_g) && $item->fou_g > 0) ? (float) $item->fou_g : 0);
        $sum = $first + $second + $third + $fourth;
        $divisor = ($first > 0 ? 1 : 0) + ($second > 0 ? 1 : 0) + ($third > 0 ? 1 : 0) + ($fourth > 0 ? 1 : 0);

        if ($first != 0 && $second != 0 && $third != 0 && $fourth != 0 && $divisor != 0) {
            return $sum / $divisor;
        }

        return 0;
    }

}
