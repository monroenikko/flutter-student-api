<?php

namespace App\Services;

use Illuminate\Http\Response;
use App\Services\ClassRecordService;
use App\Traits\{SchoolYear, ResponseApi};
use App\Http\Resources\GradeSheetResource;
use App\Http\Resources\SeniorGradeSheetResource;
use App\Models\StudentInformation;
use Illuminate\Support\Facades\Auth;
use App\Models\SchoolYear as SchoolYearModel;

class GradeSheetService
{
    use ResponseApi, SchoolYear;

    protected $classRecordService;
    public function __construct(ClassRecordService $classRecordService)
    {
        $this->classRecordService = $classRecordService;
    }

    public function getAll($data)
    {
        $schoolYearId = $data->get('school_year_id');

        if ($schoolYearId) {
            $school_year = SchoolYearModel::find($schoolYearId);
            if (!$school_year) {
                return $this->error('School year not found.', Response::HTTP_NOT_FOUND);
            }
            $class_detail = $this->getClassDetails($school_year->id, $sem = null);
        } else {
            $school_year = $this->activeSchoolYear();
            $class_detail = $this->getClassDetails($school_year->id, $sem = null) ?? $this->getClassDetails($school_year->id - 1, $sem = null);
        }

        $datas = [
            'section' => 'none',
            'grade_level' => 'none',
            'adviser' => 'none',
            'first_sem' => [],
            'second_sem' => [],
        ];

        if (isset($class_detail)) {
            $grade_level = $class_detail->classDetail->section->grade_level;
            if ($grade_level >= 11) {
                $sem1 = $this->getClassDetails($school_year->id, 1);
                $sem2 = $this->getClassDetails($school_year->id, 2);

                if (isset($sem1)) {
                    $sem1['sem'] = 1;
                    $first_sem = new SeniorGradeSheetResource($sem1);
                } else {
                    $first_sem = null;
                }

                if (isset($sem2)) {
                    $sem2['sem'] = 2;
                    $second_sem = new SeniorGradeSheetResource($sem2);
                } else {
                    $second_sem = null;
                }

                if (isset($sem1)) {
                    $datas = [
                        'section' => $sem1['classDetail']['section']['section'],
                        'grade_level' => $sem1['classDetail']['grade_level'],
                        'adviser' => $sem1['classDetail']['adviser']['full_name'],
                        'first_sem' => $first_sem,
                        'second_sem' => $second_sem,
                    ];
                }
            }

            if ($grade_level <= 10) {
                $datas = new GradeSheetResource($class_detail);
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
        return StudentInformation::where('user_id', Auth::user()->id)->first();
    }
}
