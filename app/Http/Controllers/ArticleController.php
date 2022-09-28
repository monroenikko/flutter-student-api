<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Services\ArticleService;

class ArticleController extends Controller
{
    protected $service;
    public function __construct(ArticleService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        return $this->service->index($request);
    }

    public function show($request, $id)
    {
        return $this->service->show($request, $id);
    }
}
