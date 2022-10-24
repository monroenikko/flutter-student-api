<?php

namespace App\Services;

use App\Traits\ResponseApi;
use Illuminate\Http\Response;
use App\Services\ClassRecordService;
use App\Http\Resources\GradeSheetResource;

class GradeSheetService
{
    use ResponseApi;

    protected $classRecordService;
    public function __construct(ClassRecordService $classRecordService)
    {
        $this->class_record_service = $classRecordService;
    }

    public function getAll($data)
    {
        $data['sem'] = 1;
        // for grade 7 - 10
        $datas = new GradeSheetResource($this->class_record_service->hasClassDetail(12, null));

        return $this->success('Data successfully listed.', Response::HTTP_OK, $datas);
    }
}