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
        $termType = $this['classDetail']['term_type'] ?? $this->classDetail->term_type ?? 'old';
        $gradeLevel = $this['classDetail']['grade_level'] ?? $this->classDetail->grade_level ?? 'none';
        $section = $this['classDetail']['section']['section'] ?? $this->classDetail->section->section ?? 'none';
        $adviser = $this['classDetail']['adviser']['full_name'] ?? $this->classDetail->adviser->full_name ?? 'none';
        $classDetailsId = $this['class_details_id'] ?? $this->class_details_id;

        if ($termType === 'new') {
            $parseHeader = function ($raw) {
                if (empty($raw)) {
                    return [];
                }
                $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
                if (is_array($decoded)) {
                    if (isset($decoded['key']) && is_array($decoded['key'])) {
                        return $decoded['key'];
                    }
                    return $decoded;
                }
                return [];
            };

            $m1 = $parseHeader($request['table_header1'] ?? $request['attendance_header']['senior1_months_header'] ?? null);
            $m2 = $parseHeader($request['table_header2'] ?? $request['attendance_header']['senior2_months_header'] ?? null);
            $m3 = $parseHeader($request['table_header3'] ?? $request['attendance_header']['senior3_months_header'] ?? null);

            $combinedHeaderKeys = array_merge($m1, $m2, $m3);
            $c1 = count($m1);
            $c2 = count($m2);
            $c3 = count($m3);

            $getArrayField = function ($jsonStr, $field) {
                if (!$jsonStr) {
                    return [];
                }
                $data = is_string($jsonStr) ? json_decode($jsonStr, true) : (array)$jsonStr;
                if (is_array($data) && isset($data[$field])) {
                    $val = $data[$field];
                    if (is_array($val)) {
                        return array_values($val);
                    }
                    if (is_object($val)) {
                        return array_values((array)$val);
                    }
                }
                return [];
            };

            $fitArray = function ($arr, $expectedCount) {
                $arr = array_values((array)$arr);
                if (empty($arr)) {
                    return $expectedCount > 0 ? array_fill(0, $expectedCount, 0) : [];
                }
                if ($expectedCount <= 0) {
                    return $arr;
                }
                if (count($arr) > $expectedCount) {
                    return array_slice($arr, 0, $expectedCount);
                }
                while (count($arr) < $expectedCount) {
                    $arr[] = 0;
                }
                return $arr;
            };

            $attFirst = $this['attendance_first'] ?? $this->attendance_first ?? null;
            $attSecond = $this['attendance_second'] ?? $this->attendance_second ?? null;
            $attThird = $this['attendance_third'] ?? $this->attendance_third ?? null;

            $dos1 = $fitArray($getArrayField($attFirst, 'days_of_school'), $c1);
            $dos2 = $fitArray($getArrayField($attSecond, 'days_of_school'), $c2);
            $dos3 = $fitArray($getArrayField($attThird, 'days_of_school'), $c3);

            $dp1 = $fitArray($getArrayField($attFirst, 'days_present'), $c1);
            $dp2 = $fitArray($getArrayField($attSecond, 'days_present'), $c2);
            $dp3 = $fitArray($getArrayField($attThird, 'days_present'), $c3);

            $da1 = $fitArray($getArrayField($attFirst, 'days_absent'), $c1);
            $da2 = $fitArray($getArrayField($attSecond, 'days_absent'), $c2);
            $da3 = $fitArray($getArrayField($attThird, 'days_absent'), $c3);

            $tt1 = $fitArray($getArrayField($attFirst, 'times_tardy'), $c1);
            $tt2 = $fitArray($getArrayField($attSecond, 'times_tardy'), $c2);
            $tt3 = $fitArray($getArrayField($attThird, 'times_tardy'), $c3);

            $dosCombined = array_merge($dos1, $dos2, $dos3);
            $dpCombined = array_merge($dp1, $dp2, $dp3);
            $daCombined = array_merge($da1, $da2, $da3);
            $ttCombined = array_merge($tt1, $tt2, $tt3);

            $dosTotal = array_sum($dosCombined);
            $dpTotal = array_sum($dpCombined);
            $daTotal = array_sum($daCombined);
            $ttTotal = array_sum($ttCombined);

            $mergedAttendanceObj = (object) [
                'days_of_school' => $dosCombined,
                'days_present' => $dpCombined,
                'days_absent' => $daCombined,
                'times_tardy' => $ttCombined,
            ];

            $totaledAttendance = $this->addTotal($mergedAttendanceObj);
            $mergedHeader = $this->addTotalHeader(['key' => $combinedHeaderKeys]);

            $attendanceData = [
                'table_header' => $mergedHeader,
                'attendance' => $totaledAttendance,
                'days_of_school_total' => $dosTotal,
                'days_present_total' => $dpTotal,
                'days_absent_total' => $daTotal,
                'times_tardy_total' => $ttTotal,
            ];

            return [
                'class_details_id' => $classDetailsId,
                'section' => $section,
                'grade_level' => $gradeLevel,
                'term_type' => 'new',
                'adviser' => $adviser,
                'attendance' => $attendanceData,
                'attendance_junior' => $attendanceData,
                'attendance_senior1' => null,
                'attendance_senior2' => null,
            ];
        }

        // Old term handling (legacy code preserved)
        $attendance = isset($this['attendance']) ? json_decode($this['attendance']) : null;
        $attendance1 = isset($this['attendance_first']) ? json_decode($this['attendance_first']) : null;
        $attendance2 = isset($this['attendance_second']) ? json_decode($this['attendance_second']) : null;

        $jDosTotal = isset($attendance->days_of_school) && is_array($attendance->days_of_school) ? array_sum($attendance->days_of_school) : 0;
        $jDpTotal  = isset($attendance->days_present) && is_array($attendance->days_present) ? array_sum($attendance->days_present) : 0;
        $jDaTotal  = isset($attendance->days_absent) && is_array($attendance->days_absent) ? array_sum($attendance->days_absent) : 0;
        $jTtTotal  = isset($attendance->times_tardy) && is_array($attendance->times_tardy) ? array_sum($attendance->times_tardy) : 0;

        $s1DosTotal = isset($attendance1->days_of_school) && is_array($attendance1->days_of_school) ? array_sum($attendance1->days_of_school) : 0;
        $s1DpTotal  = isset($attendance1->days_present) && is_array($attendance1->days_present) ? array_sum($attendance1->days_present) : 0;
        $s1DaTotal  = isset($attendance1->days_absent) && is_array($attendance1->days_absent) ? array_sum($attendance1->days_absent) : 0;
        $s1TtTotal  = isset($attendance1->times_tardy) && is_array($attendance1->times_tardy) ? array_sum($attendance1->times_tardy) : 0;

        $s2DosTotal = isset($attendance2->days_of_school) && is_array($attendance2->days_of_school) ? array_sum($attendance2->days_of_school) : 0;
        $s2DpTotal  = isset($attendance2->days_present) && is_array($attendance2->days_present) ? array_sum($attendance2->days_present) : 0;
        $s2DaTotal  = isset($attendance2->days_absent) && is_array($attendance2->days_absent) ? array_sum($attendance2->days_absent) : 0;
        $s2TtTotal  = isset($attendance2->times_tardy) && is_array($attendance2->times_tardy) ? array_sum($attendance2->times_tardy) : 0;

        if ($attendance1 && isset($attendance1->days_of_school) && is_array($attendance1->days_of_school)) {
            $data1 = $attendance1->days_of_school;
            $data1[] = (string) array_sum($attendance1->days_of_school);
            unset($attendance1->days_of_school);

            $result = [];
            foreach ($data1 as $value) {
                if ($value !== 0) {
                    $result[] = (string)$value;
                }
            }
            $attendance1->days_of_school = $result;
        }

        return [
            'class_details_id' => $classDetailsId,
            'section' => $section,
            'grade_level' => $gradeLevel,
            'term_type' => 'old',
            'adviser' => $adviser,
            'attendance_junior' => [
                'table_header' => $this->addTotalHeader($request['table_header'] ?? ['key' => []]),
                'attendance' => $this->addTotal($attendance),
                'days_of_school_total' => $jDosTotal,
                'days_present_total' => $jDpTotal,
                'days_absent_total' => $jDaTotal,
                'times_tardy_total' => $jTtTotal,
            ],
            'attendance_senior1' => [
                'table_header' => $this->addTotalHeader($request['table_header1'] ?? ['key' => []]),
                'attendance' => $this->addTotal($attendance1 ? (object) $attendance1 : null),
                'days_of_school_total' => $s1DosTotal,
                'days_present_total' => $s1DpTotal,
                'days_absent_total' => $s1DaTotal,
                'times_tardy_total' => $s1TtTotal,
            ],
            'attendance_senior2' => [
                'table_header' => $this->addTotalHeader($request['table_header2'] ?? ['key' => []]),
                'attendance' => $this->addTotal($attendance2 ? (object) $attendance2 : null),
                'days_of_school_total' => $s2DosTotal,
                'days_present_total' => $s2DpTotal,
                'days_absent_total' => $s2DaTotal,
                'times_tardy_total' => $s2TtTotal,
            ],
        ];
    }

    private function addTotalHeader($header)
    {
        $tHeader = is_array($header) ? $header : ['key' => []];
        if (!isset($tHeader['key']) || !is_array($tHeader['key'])) {
            $tHeader['key'] = [];
        }
        $tHeader['key'][] = "Total";
        return $tHeader;
    }

    private function addTotal($attendance)
    {
        if (!$attendance) {
            return (object) [
                'days_of_school' => [],
                'days_present' => [],
                'days_absent' => [],
                'times_tardy' => [],
            ];
        }

        $att = is_object($attendance) ? clone $attendance : (object) $attendance;

        $school = $att->days_of_school ?? [];
        $school = array_map(function ($value) {
            return (string) $value;
        }, (array) $school);
        $school[] = (string) array_sum($school);
        $att->days_of_school = $school;

        $present = array_slice($att->days_present ?? [], 0, count($school) - 1);
        $present[] = array_sum($present);
        $att->days_present = $present;

        $absent = array_slice($att->days_absent ?? [], 0, count($school) - 1);
        $absent[] = array_sum($absent);
        $att->days_absent = $absent;

        $tardy = array_slice($att->times_tardy ?? [], 0, count($school) - 1);
        $tardy[] = array_sum($tardy);
        $att->times_tardy = $tardy;

        return $att;
    }
}
