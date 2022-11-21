<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ClassDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $attendance = json_decode($this['attendance']);
        $attendance1 = json_decode($this['attendance_first']);
        $attendance2 = json_decode($this['attendance_second']);

		$data1 = $attendance1->days_of_school;
        $data1[]=(string) array_sum($attendance1->days_of_school);
        unset($attendance1->days_of_school);

        $result = [];
        foreach($data1 as $value) {
            if ((int)$value !== 0) {
                $result[] = (int)$value;
            }
        }
        $attendance1->days_of_school = $result;
        // dd($attendance1);

        return [
            'class_details_id' => $this['class_details_id'],
            'section' => $this['classDetail']['section']['section'],
            'grade_level' => $this['classDetail']['grade_level'],
            'adviser' => $this['classDetail']['adviser']['full_name'],
            'attendance_junior' => [
                'table_header' => $request['table_header'],
                'attendance' => $this->addTotal((object) $attendance),
                'days_of_school_total' => array_sum($attendance->days_of_school),
                'days_present_total' => array_sum($attendance->days_present),
                'days_absent_total' => array_sum($attendance->days_absent),
                'times_tardy_total' => array_sum($attendance->times_tardy),
            ],
            'attendance_senior1' => [
                'table_header' => $request['table_header1'],
                'attendance' => $attendance1,
                'days_of_school_total' => array_sum($attendance1->days_of_school),
                'days_present_total' => array_sum($attendance1->days_present),
                'days_absent_total' => array_sum($attendance1->days_absent),
                'times_tardy_total' => array_sum($attendance1->times_tardy),
            ],
            'attendance_senior2' => [
                'table_header' => $request['table_header2'],
                'attendance' => $this->addTotal((object) $attendance2),
                'days_of_school_total' => array_sum($attendance2->days_of_school),
                'days_present_total' => array_sum($attendance2->days_present),
                'days_absent_total' => array_sum($attendance2->days_absent),
                'times_tardy_total' => array_sum($attendance2->times_tardy),
            ],
        ];
    }

    private function addTotal(object $attendance)
    {
        $school = $attendance->days_of_school;
        $school[]=(string) array_sum($attendance->days_of_school);
        unset($attendance->days_of_school);
        $attendance->days_of_school = $school;

        $present = $attendance->days_present;
        $present[]=array_sum($attendance->days_present);
        unset($attendance->days_present);
        $attendance->days_present = $present;

        $absent = $attendance->days_absent;
        $absent[]= array_sum($attendance->days_absent);
        unset($attendance->days_absent);
        $attendance->days_absent = $absent;

        $tardy = $attendance->times_tardy;
        $tardy[]= array_sum($attendance->times_tardy);
        unset($attendance->times_tardy);
        $attendance->times_tardy = $tardy;

        return $attendance;
    }
}