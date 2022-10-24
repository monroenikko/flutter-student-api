<?php

namespace App\Traits;

use App\Models\SchoolYear as ModelsSchoolYear;

Trait SchoolYear
{
    public function activeSchoolYear()
    {
        return ModelsSchoolYear::whereCurrent(1)->whereStatus(1)->first();
    }
}
