<?php

namespace App\Http\Controllers;

use App\Services\ClassScheduleService;
use Illuminate\Http\Request;

class ClassScheduleController extends Controller
{
    public function __construct(
        protected ClassScheduleService $service
    ) {}

    public function index(Request $request)
    {
        return $this->service->getStudentClassSchedule($request);
    }
}
