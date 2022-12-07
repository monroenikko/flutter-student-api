<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AuthService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\RegistrationRequest;

class AuthController extends Controller
{
    protected $service;

    public function __construct(AuthService $service)
    {
        $this->service = $service;
    }

    public function register(RegistrationRequest $request)
    {
        return $this->service->register($request->validated());
    }

    public function login(LoginRequest $request)
    {
        return $this->service->login($request->validated());
    }

    public function userData(Request $request)
    {
        return $this->service->userData($request);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string'
        ]);
        $image = null;
        if($request->image){
            $image = $this->saveImage($request->image, 'profiles');
        }
        return $this->service->update($data, $image);
    }

    public function logout(Request $request)
    {
        return $this->service->logout($request);
    }

    public function refresh(Request $request)
    {
        return $this->service->refresh($request);
    }
}