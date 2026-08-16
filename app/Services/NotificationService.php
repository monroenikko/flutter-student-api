<?php

namespace App\Services;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Traits\ResponseApi;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    use ResponseApi;

    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $notifications = $user->notifications()->paginate(15);
            $unreadCount = $user->unreadNotifications()->count();

            return $this->success(
                'Notifications retrieved successfully.',
                Response::HTTP_OK,
                [
                    'notifications' => $notifications,
                    'unread_count' => $unreadCount,
                ]
            );
        } catch (Exception $e) {
            Log::error($e);
            return $this->error($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function markRead(Request $request, $id)
    {
        try {
            $user = $request->user();
            $notification = $user->notifications()->where('id', $id)->first();

            if (!$notification) {
                return $this->error('Notification not found.', Response::HTTP_NOT_FOUND);
            }

            $notification->markAsRead();

            return $this->success('Notification marked as read.', Response::HTTP_OK, []);
        } catch (Exception $e) {
            Log::error($e);
            return $this->error($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function markUnread(Request $request, $id)
    {
        try {
            $user = $request->user();
            $notification = $user->notifications()->where('id', $id)->first();

            if (!$notification) {
                return $this->error('Notification not found.', Response::HTTP_NOT_FOUND);
            }

            $notification->update(['read_at' => null]);

            return $this->success('Notification marked as unread.', Response::HTTP_OK, []);
        } catch (Exception $e) {
            Log::error($e);
            return $this->error($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function markAllRead(Request $request)
    {
        try {
            $user = $request->user();
            $user->unreadNotifications->markAsRead();

            return $this->success('All notifications marked as read.', Response::HTTP_OK, []);
        } catch (Exception $e) {
            Log::error($e);
            return $this->error($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function markAllUnread(Request $request)
    {
        try {
            $user = $request->user();
            $user->notifications()->update(['read_at' => null]);

            return $this->success('All notifications marked as unread.', Response::HTTP_OK, []);
        } catch (Exception $e) {
            Log::error($e);
            return $this->error($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
