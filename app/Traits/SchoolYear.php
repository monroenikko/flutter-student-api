<?php

namespace App\Traits;

use App\Models\SchoolYear as ModelsSchoolYear;

trait SchoolYear
{
    public function activeSchoolYear()
    {
        return ModelsSchoolYear::whereCurrent(1)->whereStatus(1)->first();
    }

    public function querySchoolYear($id)
    {
        return ModelsSchoolYear::where('status', 1)->where('id', $id)->first();
    }

    public function schoolYear()
    {
        return $this->hasOne(ModelsSchoolYear::class, 'id', 'school_year_id')->orderBy('school_year', 'DESC');
    }

    public function lastYear()
    {
        return $this->hasOne(ModelsSchoolYear::class, 'id', 'last_sy_attended');
    }

    public function schoolYears()
    {
        return ModelsSchoolYear::where('status', 1)->orderBy('school_year', 'DESC')->get();
    }

    public function schoolYearActiveStatus()
    {
        return ModelsSchoolYear::where('current', 1)->where('status', 1)->first();
    }

    public function accountSchoolYears()
    {
        return ModelsSchoolYear::where('status', 1)->orderBy('school_year', 'DESC')->get();
    }

    public function schoolYearLatest()
    {
        return ModelsSchoolYear::where('current', 1)
            ->where('status', 1)->orderBy('id', 'desc')->first();
    }
}
