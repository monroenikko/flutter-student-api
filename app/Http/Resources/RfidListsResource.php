<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use App\Models\RfidLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Resources\Json\JsonResource;

class RfidListsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $message = "No Record";
        return [
            'data' => $this->map(function ($item) use ($message, $request) {
                $checkin = $this->rfidLogs($item->date, 1, $request)->format('Y-m-d H:i:s');
                $checkout = $this->rfidLogs($item->date, 0, $request)->format('Y-m-d H:i:s');
                $checkoutVariable = $this->rfidLogs($item->date, 0, $request)->addHour(2)->format('Y-m-d H:i:s');
                return [
                    "date" => $this->rfidLogs($item->date, NULL, $request) !== NULL ? $this->rfidLogs($item->date, NULL, $request)->format('Y-m-d') : $message,
                    "checkin" =>  $checkin !== NULL ? $checkin : $message,
                    "checkout" => isset($checkout) ? ($checkout === $checkin ? $message : $checkout) : $message,
                ];
            }),
            'pagination' => [
                'total' => (int) $this->total(),
                'count' => (int) is_null($this->lastItem()) ? 0 : $this->lastItem(),
                'per_page' => (int)$this->perPage(),
                'current_page' => $this->currentPage(),
                'total_pages' => (int) $this->lastPage(),
                'previous_page_url' => is_null($this->previousPageUrl()) ? '0' : $this->previousPageUrl(),
                'last_page' => $this->lastPage(),
                'first_page_url' => $this->onFirstPage(),
                'next_page_url' => $this->nextPageUrl(),
                'from' => $this->lastItem(),
                'to' =>(int)$this->perPage(),
            ]
        ];
    }

    private function rfidLogs($date, $order,$request)
    {
        $sort = ['DESC', 'ASC'];
        $data = RfidLog::whereDate('created_at', '=', $date)
            ->when($request != [], function($q) use($request){
                $q->where('rfid_information_id', $request['rfid_information_id']);
            })

            ->when($order !== NULL, function($q) use($sort, $order){
                $q->orderBy('created_at', $sort[$order]);
            })->first();
        return $data ? Carbon::parse($data->created_at) : NULL;
    }
}
