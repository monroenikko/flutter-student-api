<?php

namespace App\Services;

use Illuminate\Http\Response;
use App\Services\ClassRecordService;
use App\Traits\{SchoolYear,ResponseApi};
use App\Http\Resources\GradeSheetResource;
use App\Http\Resources\SeniorGradeSheetResource;

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
        $school_year = $this->activeSchoolYear();
        $class_detail = $this->getClassDetails($school_year->id, $sem = null) ?? $this->getClassDetails($school_year->id-1, $sem = null);

        $datas = [
            'section' => 'none',
            'grade_level' => 'none',
            'adviser' => 'none',
            'first_sem' => [],
            'second_sem' => [],
        ];
        
        if(isset($class_detail)){
            $grade_level = $class_detail->classDetail->section->grade_level;
            if($grade_level >= 11)
            {
                $sem1 = $this->getClassDetails($school_year->id, 1);
                $sem1['sem'] = 1;
                $first_sem = isset($sem1) ? new SeniorGradeSheetResource($sem1) : null;


                $sem2 = $this->getClassDetails($school_year->id, 2);
                isset($sem2) ? $sem2['sem'] = 2 : null;
                $second_sem = isset($sem2) ? new SeniorGradeSheetResource($sem2) : null;

                $datas = [
                    'section' => $sem1['classDetail']['section']['section'],
                    'grade_level' => $sem1['classDetail']['grade_level'],
                    'adviser' => $sem1['classDetail']['adviser']['full_name'],
                    'first_sem' => $first_sem,
                    'second_sem' => $second_sem,
                ];
            }

            if($grade_level <= 10)
            {
                $datas = new GradeSheetResource($class_detail);
            }
        }

        return $this->success('Data successfully listed.', Response::HTTP_OK, $datas);
    }

    private function getClassDetails($schoolYearId, $sem)
    {
        return $this->classRecordService->hasClassDetail($schoolYearId, $sem);
    }
}