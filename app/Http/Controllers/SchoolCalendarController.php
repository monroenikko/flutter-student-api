<?php

namespace App\Http\Controllers;

use App\Services\SchoolCalendarService;
use Illuminate\Http\Request;

class SchoolCalendarController extends Controller
{
    protected $service;

    public function __construct(SchoolCalendarService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        return $this->service->index($request);
    }

    public function show($id)
    {
        return $this->service->show($id);
    }
}
