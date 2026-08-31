<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SiblingService;

class SiblingController extends Controller
{
    protected $service;

    public function __construct(SiblingService $service)
    {
        $this->service = $service;
    }

    /**
     * Get all linked siblings for the authenticated user.
     */
    public function index()
    {
        return $this->service->index();
    }
}
