<?php

namespace App\Http\Controllers;

use App\Services\AppVersionService;
use Illuminate\Http\Request;

class AppVersionController extends Controller
{
    protected $service;

    public function __construct(AppVersionService $service)
    {
        $this->service = $service;
    }

    /**
     * Check mobile app version.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function check(Request $request)
    {
        return $this->service->check($request);
    }
}
