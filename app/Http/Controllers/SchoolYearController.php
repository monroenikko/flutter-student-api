<?php

namespace App\Http\Controllers;

use App\Services\SchoolYearService;
use App\Traits\{ResponseApi, HasSiblingAccess};
use App\Models\StudentInformation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SchoolYearController extends Controller
{
    use ResponseApi, HasSiblingAccess;

    protected $service;

    public function __construct(SchoolYearService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $studentId = $request->get('student_id');
        $studentInformation = $studentId
            ? $this->getAuthorizedStudent($studentId)
            : StudentInformation::studentInfo();

        if (!$studentInformation) {
            return $this->error('Student information not found.', Response::HTTP_NOT_FOUND);
        }

        $schoolYears = $this->service->getAll($request, $studentInformation);

        return $this->success('School years successfully listed.', Response::HTTP_OK, $schoolYears);
    }

    public function show(Request $request)
    {
        return $this->service->getById($request);
    }

    private function student()
    {
        return $this->getAuthorizedStudent(request('student_id'));
    }
}
