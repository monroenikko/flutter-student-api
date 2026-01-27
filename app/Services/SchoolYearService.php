<?php

namespace App\Services;

use App\Models\SchoolYear;

class SchoolYearService
{
    protected $schoolYear;
    public function __construct(SchoolYear $schoolYear)
    {
        $this->school_year = $schoolYear;
    }

    public function getAll()
    {
        return $this->school_year->filter()->paginate(10);
    }

    public function getById($id)
    {
        return $this->school_year->filter()->first();
    }
}
