<?php

namespace App\Services;

use Illuminate\Http\Response;
use App\Services\ClassRecordService;
use App\Traits\{SchoolYear, ResponseApi, HasSiblingAccess};
use App\Http\Resources\GradeSheetResource;
use App\Http\Resources\SeniorGradeSheetResource;
use App\Models\StudentInformation;
use Illuminate\Support\Facades\Auth;
use App\Models\GradeReleaseSchedule;
use App\Models\SchoolYear as SchoolYearModel;

class GradeSheetService
{
    use ResponseApi, SchoolYear, HasSiblingAccess;

    protected $classRecordService;
    public function __construct(ClassRecordService $classRecordService)
    {
        $this->classRecordService = $classRecordService;
    }

    public function getAll($data)
    {
        $student = $this->student();
        if (!$student) {
            return $this->error('Student information not found.', Response::HTTP_NOT_FOUND);
        }

        $schoolYearId = $data->get('school_year_id');

        if ($schoolYearId) {
            $school_year = SchoolYearModel::find($schoolYearId);
            if (!$school_year) {
                return $this->error('School year not found.', Response::HTTP_NOT_FOUND);
            }
            $class_detail = $this->getClassDetails($school_year->id, $sem = null);
        } else {
            $school_year = $this->activeSchoolYear();
            $class_detail = $school_year
                ? ($this->getClassDetails($school_year->id, $sem = null) ?? $this->getClassDetails($school_year->id - 1, $sem = null))
                : null;
        }

        $datas = [
            'section' => 'none',
            'grade_level' => 'none',
            'adviser' => 'none',
            'grade_release_schedules' => [],
            'first_sem' => [],
            'second_sem' => [],
        ];

        if (isset($class_detail)) {
            $term_type = $class_detail->classDetail->term_type ?? 'old';
            $grade_level = $class_detail->classDetail->section->grade_level ?? $class_detail->classDetail->grade_level ?? 0;

            $releaseSchedules = collect();
            if (isset($school_year)) {
                $releaseSchedules = GradeReleaseSchedule::where('school_year_id', $school_year->id)
                    ->where('term_type', $term_type)
                    ->orderBy('term')
                    ->get();

                if ($releaseSchedules->isEmpty()) {
                    $releaseSchedules = GradeReleaseSchedule::where('school_year_id', $school_year->id)
                        ->orderBy('term')
                        ->get();
                }
            }

            if ($term_type === 'new') {
                $datas = new GradeSheetResource($class_detail, $releaseSchedules);
            } else {
                if ($grade_level >= 11) {
                    $sem1 = $this->getClassDetails($school_year->id, 1);
                    $sem2 = $this->getClassDetails($school_year->id, 2);

                    if (isset($sem1)) {
                        $sem1['sem'] = 1;
                        $first_sem = new SeniorGradeSheetResource($sem1, $releaseSchedules);
                    } else {
                        $first_sem = null;
                    }

                    if (isset($sem2)) {
                        $sem2['sem'] = 2;
                        $second_sem = new SeniorGradeSheetResource($sem2, $releaseSchedules);
                    } else {
                        $second_sem = null;
                    }

                    $target = $sem1 ?? $sem2;
                    if ($target) {
                        $datas = [
                            'section' => $target['classDetail']['section']['section'] ?? $class_detail->classDetail->section->section ?? 'none',
                            'grade_level' => $target['classDetail']['grade_level'] ?? $class_detail->classDetail->grade_level ?? 'none',
                            'term_type' => 'old',
                            'adviser' => $target['classDetail']['adviser']['full_name'] ?? $class_detail->classDetail->adviser->full_name ?? 'none',
                            'grade_release_schedules' => GradeSheetResource::formatSchedules($releaseSchedules),
                            'first_sem' => $first_sem,
                            'second_sem' => $second_sem,
                        ];
                    }
                }

                if ($grade_level <= 10) {
                    $datas = new GradeSheetResource($class_detail, $releaseSchedules);
                }
            }
        }

        return $this->success('Data successfully listed.', Response::HTTP_OK, $datas);
    }

    private function getClassDetails($schoolYearId, $sem)
    {
        return $this->classRecordService->hasClassDetail($schoolYearId, $sem);
    }

    public function getSchoolYears()
    {
        $StudentInformation = $this->student();

        if (!$StudentInformation) {
            return $this->error('Student information not found.', Response::HTTP_NOT_FOUND);
        }

        $SchoolYears = SchoolYearModel::whereIn('id', function ($query) use ($StudentInformation) {
            $query->select('class_details.school_year_id')
                ->from('enrollments')
                ->join('class_details', 'class_details.id', '=', 'enrollments.class_details_id')
                ->where('enrollments.student_information_id', $StudentInformation->id)
                ->where('enrollments.status', 1);
        })
        ->orderBy('school_year', 'desc')
        ->select('id', 'school_year', 'status', 'current')
        ->get();

        return $this->success('School years successfully listed.', Response::HTTP_OK, $SchoolYears);
    }

    private function student()
    {
        return $this->getAuthorizedStudent(request('student_id'));
    }
}
