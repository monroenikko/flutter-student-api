<?php

namespace App\Services;

use App\Models\SchoolYear;

class SchoolYearService
{
    protected $schoolYear;

    public function __construct(SchoolYear $schoolYear)
    {
        $this->schoolYear = $schoolYear;
    }

    public function getAll($data, $studentInformation)
    {
        $SchoolYears = SchoolYear::whereIn('id', function ($query) use ($studentInformation) {
            $query->select('class_details.school_year_id')
                ->from('enrollments')
                ->join('class_details', 'class_details.id', '=', 'enrollments.class_details_id')
                ->where('enrollments.student_information_id', $studentInformation->id)
                ->where('enrollments.status', 1);
        })
            ->orderBy('school_year', 'desc')
            ->select('id', 'school_year')
            ->get();

        return $SchoolYears;
    }

    public function getById($id)
    {
        return $this->schoolYear->filter()->select('id', 'school_year')->first();
    }
}
