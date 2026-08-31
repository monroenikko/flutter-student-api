<?php

namespace App\Services;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Traits\{ResponseApi, HasSiblingAccess};
use App\Models\User;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    use ResponseApi, HasSiblingAccess;

    private function resolveUser(Request $request)
    {
        $authUser = $request->user();
        $studentId = $request->get('student_id');

        if ($studentId) {
            $student = $this->getAuthorizedStudent($studentId);
            if ($student && $student->user_id && $student->user_id != $authUser->id) {
                $targetUser = User::find($student->user_id);
                if ($targetUser) {
                    return $targetUser;
                }
            }
        }

        return $authUser;
    }

    public function index(Request $request)
    {
        try {
            $user = $this->resolveUser($request);
            $notifications = $user->notifications()->latest()->paginate(15);
            $unreadCount = $user->unreadNotifications()->count();

            $notifications->through(function ($item) {
                $data = is_array($item->data) ? $item->data : (json_decode((string) $item->data, true) ?? []);
                return [
                    'id'                => $item->id,
                    'type'              => $item->type,
                    'data'              => $data,
                    'title'             => $data['title'] ?? 'Notification',
                    'message'           => $data['message'] ?? '',
                    'notification_type' => $data['type'] ?? 'general',
                    'term'              => $data['term'] ?? null,
                    'term_type'         => $data['term_type'] ?? null,
                    'school_year_id'    => $data['school_year_id'] ?? null,
                    'student_id'        => $data['student_id'] ?? null,
                    'read_at'           => $item->read_at,
                    'is_read'           => !is_null($item->read_at),
                    'created_at'        => $item->created_at ? $item->created_at->toIso8601String() : null,
                    'formatted_date'    => $item->created_at ? $item->created_at->format('M d, Y g:i A') : '',
                    'time_ago'          => $item->created_at ? $item->created_at->diffForHumans() : '',
                ];
            });

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
            $user = $this->resolveUser($request);
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
            $user = $this->resolveUser($request);
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
            $user = $this->resolveUser($request);
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
            $user = $this->resolveUser($request);
            $user->notifications()->update(['read_at' => null]);

            return $this->success('All notifications marked as unread.', Response::HTTP_OK, []);
        } catch (Exception $e) {
            Log::error($e);
            return $this->error($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
