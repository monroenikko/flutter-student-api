<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RfidService;

class RfidController extends Controller
{
    protected $service;

    public function __construct(RfidService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
       return $this->service->index($request);
    }
}
