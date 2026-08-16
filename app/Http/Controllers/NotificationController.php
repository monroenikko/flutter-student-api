<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\NotificationService;

class NotificationController extends Controller
{
    protected $service;

    public function __construct(NotificationService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        return $this->service->index($request);
    }

    public function markRead(Request $request, $id)
    {
        return $this->service->markRead($request, $id);
    }

    public function markUnread(Request $request, $id)
    {
        return $this->service->markUnread($request, $id);
    }

    public function markAllRead(Request $request)
    {
        return $this->service->markAllRead($request);
    }

    public function markAllUnread(Request $request)
    {
        return $this->service->markAllUnread($request);
    }
}
