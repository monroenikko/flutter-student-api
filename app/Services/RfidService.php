<?php
namespace App\Services;

use App\Traits\{ SchoolYear, ResponseApi };
use Illuminate\Http\Response;
use App\Http\Resources\RfidListsResource;
use Illuminate\Support\Facades\{DB, Auth};
use App\Models\{RfidInformation, StudentInformation, RfidLog};

class RfidService
{
    use ResponseApi, SchoolYear;

    protected $rfid_logs, $students, $rfidInformation, $schoolYearId;

    public function __construct(StudentInformation $students, RfidInformation $rfidInformation)
    {
        $this->rfid_logs = DB::table('rfid_logs');
        $this->students = $students;
        $this->rfidInformation = $rfidInformation;
        $this->schoolYearId = $this->activeSchoolYear()->id;
    }

    public function index($request)
    {
        $profile = $this->students->where('user_id', Auth::user()->id)->first();

        if(!$profile)
        {
            return $this->error(
                'No Data Found.', Response::HTTP_BAD_REQUEST, []
            );
        }

        $rfidInformation = $this->rfidInformation->where('student_information_id', $profile->id)->where('school_year_id', $this->schoolYearId)->first();

        if(!$rfidInformation)
        {
            return $this->error(
                'No Data Found.', Response::HTTP_BAD_REQUEST, null
            );
        }

        // return $this->rfid_logs
        //         ->where('rfid_information_id', $rfidInformation->id)
        //         ->selectRaw('*')
        //         // ->groupBy('date')
        //         ->orderBy('created_at','desc')
        //         ->paginate(isset($request->limit) ? $request->limit : 10);

        $request['rfid_information_id'] = $rfidInformation->id;

        $data = new RfidListsResource($this->rfid_logs->where('rfid_information_id', $rfidInformation->id)
                ->select(DB::raw('Date(created_at) as date'))
                ->groupBy('date')
                ->orderBy('date','desc')
                ->paginate(isset($request->limit) ? $request->limit : 10));

        return $this->success('Data successfully listed.', Response::HTTP_OK, $data);
    }
}