<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacultyInformation extends Model
{
    use HasFactory;

    protected $table="faculty_informations";

    public function getFullNameAttribute() {
        return ucwords($this->last_name . ', ' . $this->first_name. ' ' . $this->middle_name);
    }

}
