<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\{ Request, Response };
use App\Models\StudentAttendance;
use App\Services\ClassRecordService;
use App\Traits\{ SchoolYear, ResponseApi };
use App\Http\Resources\ClassDetailResource;

class ClassDetailController extends Controller
{
    use ResponseApi, SchoolYear;
    protected $service;

    public function __construct(ClassRecordService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        try {
            $school_year_id = $this->activeSchoolYear()->id;
            $attendance_header = StudentAttendance::whereSchoolYearId($school_year_id)->first() ?? StudentAttendance::whereSchoolYearId($school_year_id-1)->first();
            $request['table_header'] = isset($attendance_header) ? json_decode($attendance_header['junior_months_header'], true) : [];
            $request['table_header1'] = isset($attendance_header) ? json_decode($attendance_header['senior1_months_header'], true) : [];
            $request['table_header2'] = isset($attendance_header) ? json_decode($attendance_header['senior2_months_header'], true) : [];

            $data = $attendance_header ? new ClassDetailResource($this->service->hasClassDetail($school_year_id, null) ?? $this->service->hasClassDetail($school_year_id-1, null)) : [];
            return $this->success('Data successfully listed.', Response::HTTP_OK, $data);
        } catch (Exception $e) {
            // dd($e);
            return $this->error('Something went wrong.', Response::HTTP_BAD_REQUEST, $e->getMessage());
        }
    }
}