<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionOtherFeeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'transaction_id' => $this->transaction_id,
            'or_no' => $this->or_no,
            'student_id' => $this->student_id,
            'others_fee_id' => $this->others_fee_id,
            'school_year_id' => $this->school_year_id,
            'other_name' => $this->other_name,
            'item_qty' => $this->item_qty,
            'item_price' => $this->item_price,
            'isSuccess' => $this->isSuccess,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'other_fee' => $this->whenLoaded('otherFee', function () {
                return [
                    'other_fee_name' => $this->otherFee->other_fee_name,
                    'other_fee_amt' => $this->otherFee->other_fee_amt,
                ];
            }),
        ];
    }
}
