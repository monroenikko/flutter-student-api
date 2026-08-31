<?php

namespace App\Services;

use App\Http\Resources\SchoolCalendarEventResource;
use App\Models\SchoolCalendarEvent;
use App\Traits\ResponseApi;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SchoolCalendarService
{
    use ResponseApi;

    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $targetYear = $request->filled('year') ? (int) $request->year : (int) date('Y');
            $this->ensureHolidaysForYear($targetYear);

            $query = SchoolCalendarEvent::forStudent($user);

            // Date filtering
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->whereBetween('event_date', [$request->start_date, $request->end_date]);
            } elseif ($request->filled('year') && $request->filled('month')) {
                $query->whereYear('event_date', (int) $request->year)
                    ->whereMonth('event_date', (int) $request->month);
            } elseif ($request->filled('year')) {
                $query->whereYear('event_date', (int) $request->year);
            }

            // Type filtering
            if ($request->filled('type') && $request->type !== 'all') {
                $query->where('type', strtolower($request->type));
            }

            // Search query
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $events = $query->orderBy('event_date', 'asc')
                ->orderBy('start_time', 'asc')
                ->get();

            $formattedEvents = SchoolCalendarEventResource::collection($events)->resolve();

            return $this->success(
                'School calendar events successfully fetched.',
                Response::HTTP_OK,
                [
                    'events' => $formattedEvents,
                    'counts' => $this->typeCounts($user, $request),
                ]
            );
        } catch (Exception $e) {
            Log::error($e);
            return $this->error('Failed to fetch school calendar events: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id)
    {
        try {
            $user = Auth::user();
            $event = SchoolCalendarEvent::forStudent($user)->where('id', $id)->first();

            if (!$event) {
                return $this->error('Calendar event not found.', Response::HTTP_NOT_FOUND);
            }

            return $this->success(
                'Calendar event details fetched successfully.',
                Response::HTTP_OK,
                new SchoolCalendarEventResource($event)
            );
        } catch (Exception $e) {
            Log::error($e);
            return $this->error('Failed to fetch calendar event: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function typeCounts($user, Request $request): array
    {
        $base = SchoolCalendarEvent::forStudent($user);

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $base->whereBetween('event_date', [$request->start_date, $request->end_date]);
        } elseif ($request->filled('year') && $request->filled('month')) {
            $base->whereYear('event_date', (int) $request->year)
                ->whereMonth('event_date', (int) $request->month);
        } elseif ($request->filled('year')) {
            $base->whereYear('event_date', (int) $request->year);
        }

        return [
            'all'      => (clone $base)->count(),
            'holiday'  => (clone $base)->where('type', 'holiday')->count(),
            'event'    => (clone $base)->where('type', 'event')->count(),
            'reminder' => (clone $base)->where('type', 'reminder')->count(),
            'note'     => (clone $base)->where('type', 'note')->count(),
        ];
    }

    public function ensureHolidaysForYear($year)
    {
        if (!$year) {
            $year = (int) date('Y');
        }

        $hasHolidays = SchoolCalendarEvent::where('type', 'holiday')
            ->whereYear('event_date', $year)
            ->exists();

        if ($hasHolidays) {
            return;
        }

        $holidays = [
            ['date' => "{$year}-01-01", 'title' => "New Year's Day", 'description' => 'Bagong Taon - Regular Holiday'],
            ['date' => "{$year}-04-09", 'title' => 'Araw ng Kagitingan', 'description' => 'Day of Valor - Regular Holiday'],
            ['date' => "{$year}-05-01", 'title' => 'Labor Day', 'description' => 'Araw ng Paggawa - Regular Holiday'],
            ['date' => "{$year}-06-12", 'title' => 'Independence Day', 'description' => 'Araw ng Kalayaan - Regular Holiday'],
            ['date' => "{$year}-08-21", 'title' => 'Ninoy Aquino Day', 'description' => 'Araw ng Kamatayan ni Senador Benigno Simeon "Ninoy" Aquino Jr.'],
            ['date' => "{$year}-08-31", 'title' => 'National Heroes Day', 'description' => 'Araw ng mga Bayani (National Heroes Day)'],
            ['date' => "{$year}-11-01", 'title' => "All Saints' Day", 'description' => 'Todos los Santos - Special Non-Working Day'],
            ['date' => "{$year}-11-02", 'title' => "All Souls' Day", 'description' => 'Special Non-Working Day'],
            ['date' => "{$year}-11-30", 'title' => 'Bonifacio Day', 'description' => 'Araw ni Bonifacio - Regular Holiday'],
            ['date' => "{$year}-12-08", 'title' => 'Feast of the Immaculate Conception of Mary', 'description' => 'Special Non-Working Day'],
            ['date' => "{$year}-12-24", 'title' => 'Christmas Eve', 'description' => 'Special Non-Working Day'],
            ['date' => "{$year}-12-25", 'title' => 'Christmas Day', 'description' => 'Araw ng Pasko - Regular Holiday'],
            ['date' => "{$year}-12-30", 'title' => 'Rizal Day', 'description' => 'Araw ni Rizal - Regular Holiday'],
            ['date' => "{$year}-12-31", 'title' => 'Last Day of the Year', 'description' => 'Special Non-Working Day'],
        ];

        foreach ($holidays as $h) {
            SchoolCalendarEvent::updateOrCreate(
                [
                    'event_date' => $h['date'],
                    'title'      => $h['title'],
                    'type'       => 'holiday',
                ],
                [
                    'description'      => $h['description'],
                    'is_broadcast'     => 1,
                    'audience'         => 'all',
                    'created_by_admin' => 1,
                ]
            );
        }
    }
}
