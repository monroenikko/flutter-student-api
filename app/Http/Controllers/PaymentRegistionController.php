<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use App\Traits\ResponseApi;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PaymentRegistionController extends Controller
{
    use ResponseApi;

    protected $service;

    public function __construct(PaymentService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        try {
            $data = $this->service->index($request);
            return $this->success('Data Lists Successfully Fetched.', Response::HTTP_OK, $data);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
