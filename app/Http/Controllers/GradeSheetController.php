<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GradeSheetService;

class GradeSheetController extends Controller
{
    protected $service;
    public function __construct(GradeSheetService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        return $this->service->getAll($request);
    }
}