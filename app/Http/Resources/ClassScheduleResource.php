<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassScheduleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        $classDetail = $this->resource['class_detail'] ?? null;
        $schedulesByDay = $this->resource['schedules_by_day'] ?? [];
        $days = $this->resource['days'] ?? ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'];
        $rawSchedules = $this->resource['raw_schedules'] ?? [];

        return [
            'class_details_id' => $classDetail?->id,
            'section'          => $classDetail?->section?->section ?? 'N/A',
            'grade_level'      => $classDetail?->grade_level ?? $classDetail?->section?->grade_level,
            'adviser'          => $classDetail?->adviser?->full_name ?? ($classDetail?->adviser ? trim($classDetail->adviser->first_name . ' ' . $classDetail->adviser->last_name) : '-'),
            'room'             => $classDetail?->room?->room_code ?? $classDetail?->room?->room_description ?? 'N/A',
            'school_year'      => $classDetail?->schoolYear?->school_year ?? 'N/A',
            'school_year_id'   => $classDetail?->school_year_id,
            'days'             => $days,
            'schedules_by_day' => $schedulesByDay,
            'raw_schedules'    => $rawSchedules,
        ];
    }
}
