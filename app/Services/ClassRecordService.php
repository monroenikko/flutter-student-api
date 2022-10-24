<?php
namespace App\Services;

use App\Models\{Enrollment, ClassDetail, StudentInformation};
use App\Traits\ResponseApi;
use Illuminate\Support\Facades\Auth;

class ClassRecordService
{
    use ResponseApi;

    protected $class_details, $enrollment, $studentInformation;

    public function __construct(ClassDetail $class_details, Enrollment $enrollment, StudentInformation $studentInformation)
    {
        $this->class_details = $class_details;
        $this->enrollment = $enrollment;
        $this->studentInformation = $studentInformation;
    }

    public function hasClassDetail($schoolYearId, $sem)
    {
        return  $this->enrollment->with([
                    'classDetail:id,section_id,school_year_id,adviser_id,grade_level,strand_id,status',
                    'classDetail.section:id,section,grade_level',
                    'classDetail.adviser:id,first_name,middle_name,last_name',
                    'studentEnrolledSubjects:id,subject_id,enrollments_id,class_subject_details_id,fir_g,sec_g,thi_g,fou_g,status,sem',
                    'studentEnrolledSubjects.classSubjectDetails:id,subject_id,faculty_id,class_details_id,class_subject_order,sem',
                    'studentEnrolledSubjects.classSubjectDetails.assignFaculty:id,first_name,middle_name,last_name',
                    'studentEnrolledSubjects.subjectDetails:id,subject_code,subject',
                ])
                ->whereHas('student', function($q) {
                    $q->where('id', Auth::user()->id);
                })
                ->when($schoolYearId, function($q) use ($schoolYearId) {
                    $q->whereHas('classDetail', function($q) use ($schoolYearId) {
                        $q->where('school_year_id', $schoolYearId);
                    });
                })
                ->when($sem, function($q) use ($sem) {
                    $q->whereHas('studentEnrolledSubjects', function($q) use ($sem) {
                        $q->where('sem', $sem);
                    });
                })
                ->first();
    }

}