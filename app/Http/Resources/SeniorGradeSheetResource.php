<?php

namespace App\Http\Resources;

use App\Traits\FormatsSubjectTitle;
use Illuminate\Http\Resources\Json\JsonResource;

class SeniorGradeSheetResource extends JsonResource
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
        $schedules = $this->releaseSchedules ?? $this['grade_release_schedules'] ?? collect();
        $formattedSchedules = GradeSheetResource::formatSchedules($schedules);
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
            'grades' => $this['studentEnrolledSubjects']->filter(function ($item) {
                return ($item['classSubjectDetails']['sem'] ?? $item['sem']) == $this['sem'];
            })->map(function ($item) use ($isTermRevealed) {
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

                $sem = (int) ($this['sem'] ?? 1);
                if ($sem === 1) {
                    $rawFir = (int) ($item['fir_g'] ?? 0);
                    $rawSec = (int) ($item['sec_g'] ?? 0);
                    $firG = $isTermRevealed(1) ? ($rawFir != "0.00" ? $rawFir : 0) : 0;
                    $secG = $isTermRevealed(2) ? ($rawSec != "0.00" ? $rawSec : 0) : 0;
                } else {
                    $rawThi = (int) ($item['thi_g'] ?? 0);
                    $rawFou = (int) ($item['fou_g'] ?? 0);
                    $firG = $isTermRevealed(1) ? ($rawThi != "0.00" ? $rawThi : 0) : 0;
                    $secG = $isTermRevealed(2) ? ($rawFou != "0.00" ? $rawFou : 0) : 0;
                }

                $gradeItem = [
                    'sem' => $sem,
                    'fir_g' => $firG,
                    'sec_g' => $secG,
                    'thi_g' => $firG,
                    'fou_g' => $secG,
                ];

                return [
                    'subject' => $subjectName,
                    'subject_code' => $subjectCode,
                    'is_sub_subject' => $isSubSubject,
                    'is_elective' => $isElective,
                    'fir_g' => $firG,
                    'sec_g' => $secG,
                    'final_g' => ($finalGrade = round($this->finalGrade($gradeItem))) == 0 ? '' : $finalGrade,
                    'faculty' => $item['classSubjectDetails']['assignFaculty']['full_name'] ?? $item['classSubjectDetails']['teacherSubject']['assignFaculty']['full_name'] ?? 'TBA',
                    'order' => $item['classSubjectDetails']['class_subject_order'] ?? 0,
                ];
            })
                ->sortBy('order')
                ->values()
                ->toArray()
        ];
    }

    private function finalGrade($item)
    {
        switch ((int)$item['sem']) {
            case 1:
                $first = $item['fir_g'] > 0 ? $item['fir_g'] : 0;
                $second = $item['sec_g'] > 0 ? $item['sec_g'] : 0;
                $sum = $first + $second;
                $divisor = ($first > 0 ? 1 : 0) + ($second > 0 ? 1 : 0);

                $final = 0;
                if ($first != 0 && $second != 0 && $divisor != 0) {
                    $final = $sum / $divisor;
                }
                return $final;

            case 2:
                $first = $item['thi_g'] > 0 ? $item['thi_g'] : 0;
                $second = $item['fou_g'] > 0 ? $item['fou_g'] : 0;
                $sum = $first + $second;
                $divisor = ($first > 0 ? 1 : 0) + ($second > 0 ? 1 : 0);

                $final = 0;
                if ($first != 0 && $second != 0 && $divisor != 0) {
                    $final = $sum / $divisor;
                }
                return $final;
        }

        return 0;
    }

}
