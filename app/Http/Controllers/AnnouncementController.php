<?php

namespace App\Http\Controllers;

use App\Services\AnnouncementService;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function __construct(
        protected AnnouncementService $service
    ) {}

    public function index(Request $request)
    {
        return $this->service->index($request);
    }

    public function show(int $id)
    {
        return $this->service->show($id);
    }

    public function markRead(int $id)
    {
        return $this->service->changeStatus($id, 2);
    }

    public function markUnread(int $id)
    {
        return $this->service->changeStatus($id, 1);
    }

    public function markArchived(int $id)
    {
        return $this->service->changeStatus($id, 3);
    }

    public function markAllRead()
    {
        return $this->service->markAllRead();
    }
}
