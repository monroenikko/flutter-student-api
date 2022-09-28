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
            'data' => $this->map(function ($item) use ($message) {
                $checkin = $this->rfidLogs($item->date, 1)->format('Y-m-d\TH:i:sO');
                $checkout = $this->rfidLogs($item->date, 0)->format('Y-m-d\TH:i:sO');
                $checkoutVariable = $this->rfidLogs($item->date, 0)->addHour(2)->format('Y-m-d\TH:i:sO');
                return [
                    "date" => $this->rfidLogs($item->date, NULL) !== NULL ? $this->rfidLogs($item->date, NULL)->format('Y-m-d') : $message,
                    "checkin" =>  $this->rfidLogs($item->date, 1) !== NULL ? $checkin : $message,
                    "checkout" => $this->rfidLogs($item->date, 0) !== NULL ? ( $checkoutVariable >= $checkout ? ($checkout === $checkin ? $message : $checkout) : $message) : $message,
                    // "am" => [
                    //     "checkin" => $this->rfidLogs($item->date, 1, 0) !== NULL ? $this->rfidLogs($item->date, 1, 0)->format('TH:i:sO') : $message,
                    // ],
                    // "pm" => [
                    //     "checkin" => $this->rfidLogs($item->date, 1, 1) !== NULL ?
                    //     ($this->rfidLogs($item->date, 1, 0)->format('TH:i:sO') === $this->rfidLogs($item->date, 1, 1)->format('TH:i:sO') ? $message : $this->rfidLogs($item->date, 1, 1)->format('TH:i:sO'))
                    //     : $message,
                    //     "checkout" => $this->rfidLogs($item->date, 0, 0) !== NULL ? $this->rfidLogs($item->date, 0, 0)->format('TH:i:sO') : $message,
                    // ]
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

    private function rfidLogs($date, $order)
    {
        $sort = ['DESC', 'ASC'];
        $data = RfidLog::whereDate('created_at', '=', $date)
            ->when($order !== NULL, function($q) use($sort, $order){
                $q->orderBy('created_at', $sort[$order]);
            })->first();
        return $data ? Carbon::parse($data->created_at) : NULL;
    }
}
