<?php

namespace App\Services;

use App\Http\Resources\AnnouncementResource;
use App\Models\Announcement;
use App\Models\AnnouncementUserState;
use App\Traits\{ResponseApi, HasSiblingAccess};
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AnnouncementService
{
    use ResponseApi, HasSiblingAccess;

    public function index(Request $request)
    {
        try {
            $studentLevel = $this->resolveStudentLevel();
            $search = $request->get('search');
            $status = $request->get('status', 'all');
            $title = $request->get('title');
            $sort = $request->get('sort', 'latest');
            $limit = (int) ($request->get('limit', 10));

            $query = $this->baseQuery($studentLevel, $search, $title);
            $this->applyTabFilter($query, $status);
            $this->applySort($query, $sort);

            $paginator = $query->paginate($limit);

            $data = collect($paginator->items())->map(function ($item) {
                return (new AnnouncementResource($item))->resolve();
            });

            return $this->success(
                'Announcements successfully fetched.',
                Response::HTTP_OK,
                [
                    'data'       => $data,
                    'pagination' => [
                        'total'        => $paginator->total(),
                        'per_page'     => $paginator->perPage(),
                        'current_page' => $paginator->currentPage(),
                        'last_page'    => $paginator->lastPage(),
                        'from'         => $paginator->firstItem(),
                        'to'           => $paginator->lastItem(),
                    ],
                    'counts'     => $this->statusCounts($studentLevel, $search, $title),
                    'filters'    => [
                        'status' => $status,
                        'search' => $search,
                        'title'  => $title,
                        'sort'   => $sort,
                    ],
                ]
            );
        } catch (Exception $e) {
            return $this->error('Failed to fetch announcements: ' . $e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function show(int $id)
    {
        try {
            $studentLevel = $this->resolveStudentLevel();
            $announcement = $this->announcementQuery($studentLevel)
                ->where('announcements.id', $id)
                ->first();

            if (! $announcement) {
                return $this->error('Announcement not found.', Response::HTTP_NOT_FOUND);
            }

            // Automatically mark as read if currently unread
            if ((int) $announcement->user_status === 1) {
                AnnouncementUserState::updateOrCreate(
                    [
                        'announcement_id' => $announcement->id,
                        'user_id'         => Auth::id(),
                    ],
                    [
                        'status' => 2,
                    ]
                );
                $announcement->user_status = 2;
            }

            return $this->success(
                'Announcement details fetched successfully.',
                Response::HTTP_OK,
                new AnnouncementResource($announcement)
            );
        } catch (Exception $e) {
            return $this->error('Failed to fetch announcement details: ' . $e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function changeStatus(int $id, int $status)
    {
        try {
            if (! in_array($status, [1, 2, 3])) {
                return $this->error('Invalid status value. Allowed: 1 (Unread), 2 (Read), 3 (Archived).', Response::HTTP_BAD_REQUEST);
            }

            $studentLevel = $this->resolveStudentLevel();
            $announcement = $this->announcementQuery($studentLevel)
                ->where('announcements.id', $id)
                ->first();

            if (! $announcement) {
                return $this->error('Announcement not found.', Response::HTTP_NOT_FOUND);
            }

            DB::beginTransaction();

            if ($status === 1) {
                AnnouncementUserState::where('announcement_id', $announcement->id)
                    ->where('user_id', Auth::id())
                    ->delete();
                $message = 'Announcement marked as unread.';
            } else {
                AnnouncementUserState::updateOrCreate(
                    [
                        'announcement_id' => $announcement->id,
                        'user_id'         => Auth::id(),
                    ],
                    [
                        'status' => $status,
                    ]
                );

                $message = $status === 2 ? 'Announcement marked as read.' : 'Announcement moved to archived.';
            }

            DB::commit();

            return $this->success($message, Response::HTTP_OK);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->error('Failed to update announcement status: ' . $e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function markAllRead()
    {
        try {
            $studentLevel = $this->resolveStudentLevel();
            $announcementIds = $this->announcementQuery($studentLevel)
                ->pluck('announcements.id');

            if ($announcementIds->isEmpty()) {
                return $this->success('No announcements to mark as read.', Response::HTTP_OK);
            }

            DB::beginTransaction();

            foreach ($announcementIds as $announcementId) {
                AnnouncementUserState::updateOrCreate(
                    [
                        'announcement_id' => $announcementId,
                        'user_id'         => Auth::id(),
                    ],
                    [
                        'status' => 2,
                    ]
                );
            }

            DB::commit();

            return $this->success('All announcements marked as read.', Response::HTTP_OK);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->error('Failed to mark all announcements as read: ' . $e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    private function baseQuery($studentLevel, $search = null, $title = null)
    {
        return $this->announcementQuery($studentLevel)
            ->when($search, function ($query) use ($search) {
                $query->filter($search);
            })
            ->when($title && $title !== 'all', function ($query) use ($title) {
                $query->where('announcements.title', $title);
            });
    }

    private function announcementQuery($studentLevel)
    {
        return Announcement::query()
            ->select([
                'announcements.*',
                DB::raw('COALESCE(announcement_user_states.status, 1) as user_status'),
            ])
            ->leftJoin('announcement_user_states', function ($join) {
                $join->on('announcement_user_states.announcement_id', '=', 'announcements.id')
                    ->where('announcement_user_states.user_id', Auth::id());
            })
            ->published()
            ->forStudentLevel($studentLevel);
    }

    private function applyTabFilter($query, $status)
    {
        switch ($status) {
            case 'read':
                $query->where('announcement_user_states.status', 2);
                break;
            case 'archived':
                $query->where('announcement_user_states.status', 3);
                break;
            case 'unread':
                $query->where(function ($where) {
                    $where->whereNull('announcement_user_states.status')
                        ->orWhere('announcement_user_states.status', 1);
                });
                break;
            case 'all':
            default:
                $query->where(function ($where) {
                    $where->whereNull('announcement_user_states.status')
                        ->orWhere('announcement_user_states.status', '!=', 3);
                });
                break;
        }
    }

    private function applySort($query, $sort)
    {
        switch ($sort) {
            case 'oldest':
                $query->orderBy('announcements.published_at', 'ASC');
                $query->orderBy('announcements.id', 'ASC');
                break;
            case 'title_asc':
                $query->orderBy('announcements.title', 'ASC');
                $query->orderByDesc('announcements.published_at');
                break;
            case 'title_desc':
                $query->orderByDesc('announcements.title');
                $query->orderByDesc('announcements.published_at');
                break;
            case 'latest':
            default:
                $query->orderByDesc('announcements.published_at');
                $query->orderByDesc('announcements.id');
                break;
        }
    }

    private function statusCounts($studentLevel, $search = null, $title = null): array
    {
        $base = $this->baseQuery($studentLevel, $search, $title);

        return [
            'all'      => $this->countForTab(clone $base, 'all'),
            'unread'   => $this->countForTab(clone $base, 'unread'),
            'read'     => $this->countForTab(clone $base, 'read'),
            'archived' => $this->countForTab(clone $base, 'archived'),
        ];
    }

    private function countForTab($query, $status): int
    {
        $this->applyTabFilter($query, $status);

        return (int) $query->distinct()->count('announcements.id');
    }

    private function resolveStudentLevel(): ?int
    {
        $student = $this->getAuthorizedStudent(request('student_id'));
        if (!$student) return null;

        $currentClass = DB::table('enrollments')
            ->join('class_details', 'class_details.id', '=', 'enrollments.class_details_id')
            ->where('enrollments.student_information_id', $student->id)
            ->where('enrollments.current', 1)
            ->where('enrollments.status', 1)
            ->where('class_details.current', 1)
            ->where('class_details.status', 1)
            ->select('class_details.grade_level')
            ->orderByDesc('enrollments.id')
            ->first();

        if (! $currentClass) {
            return null;
        }

        return (int) $currentClass->grade_level <= 10 ? 1 : 2;
    }
}
