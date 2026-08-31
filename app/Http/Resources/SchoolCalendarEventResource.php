<?php

namespace App\Http\Resources;

use App\Models\SchoolCalendarEvent;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SchoolCalendarEventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $userId = auth()->id();
        $eventDate = $this->event_date ? Carbon::parse($this->event_date) : null;
        $type = strtolower((string) ($this->type ?? 'note'));

        $startTimeStr = $this->start_time;
        $endTimeStr = $this->end_time;
        $formattedTime = 'All Day';

        if ($startTimeStr && $endTimeStr) {
            $formattedTime = Carbon::parse($startTimeStr)->format('g:i A') . ' - ' . Carbon::parse($endTimeStr)->format('g:i A');
        } elseif ($startTimeStr) {
            $formattedTime = Carbon::parse($startTimeStr)->format('g:i A');
        }

        $canEdit = (bool) ($userId && $this->user_id === $userId && $type !== 'holiday' && !$this->is_broadcast && !$this->created_by_admin);

        return [
            'id'               => $this->id,
            'user_id'          => $this->user_id,
            'title'            => $this->title,
            'description'      => $this->description,
            'event_date'       => $eventDate ? $eventDate->format('Y-m-d') : null,
            'formatted_date'   => $eventDate ? $eventDate->format('M d, Y') : '',
            'day_name'         => $eventDate ? $eventDate->format('D') : '',
            'day_number'       => $eventDate ? $eventDate->format('d') : '',
            'month_name'       => $eventDate ? $eventDate->format('M') : '',
            'year'             => $eventDate ? $eventDate->format('Y') : '',
            'start_time'       => $startTimeStr,
            'end_time'         => $endTimeStr,
            'formatted_time'   => $formattedTime,
            'type'             => $type,
            'type_label'       => SchoolCalendarEvent::TYPES[$type] ?? ucfirst($type),
            'type_color'       => SchoolCalendarEvent::TYPE_COLORS[$type] ?? 'badge-secondary',
            'is_broadcast'     => (bool) $this->is_broadcast,
            'audience'         => $this->audience ?? 'all',
            'created_by_admin' => (bool) $this->created_by_admin,
            'can_edit'         => $canEdit,
            'created_at'       => $this->created_at ? Carbon::parse($this->created_at)->toIso8601String() : null,
            'updated_at'       => $this->updated_at ? Carbon::parse($this->updated_at)->toIso8601String() : null,
        ];
    }
}
