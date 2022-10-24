<?php

namespace App\Http\Controllers;

use App\Services\SchoolYearService;
use Illuminate\Http\Request;

class SchoolYearController extends Controller
{
    protected $service;
    public function __construct(SchoolYearService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        return $this->service->getAll($request);
    }

    public function show(Request $request)
    {
        return $this->service->getById($request);
    }
}
