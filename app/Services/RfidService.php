<?php
namespace App\Services;

use App\Traits\ResponseApi;
use Illuminate\Http\Response;
use App\Models\{RfidInformation, StudentInformation, RfidLog};
use Illuminate\Support\Facades\{DB, Auth};
use App\Http\Resources\RfidListsResource;

class RfidService
{
    use ResponseApi;

    protected $rfid_logs, $students, $rfidInformation;

    public function __construct(RfidLog $rfid_logs, StudentInformation $students, RfidInformation $rfidInformation)
    {
        $this->rfid_logs = $rfid_logs;
        $this->students = $students;
        $this->rfidInformation = $rfidInformation;
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

        $rfidInformation = $this->rfidInformation->where('student_information_id', $profile->id)->first();

        $data = new RfidListsResource($this->rfid_logs->where('rfid_information_id', $rfidInformation->id)
                ->select(DB::raw('Date(created_at) as date'))
                ->groupBy('date')
                ->orderBy('date','desc')
                ->paginate(isset($request->limit) ? $request->limit : 10));

        return $this->success('Data successfully listed.', Response::HTTP_OK, $data);
    }
}
