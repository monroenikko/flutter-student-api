<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionDetailsResource extends JsonResource
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
            'student_category_id' => $this->student_category_id,
            'grade_level_id' => $this->grade_level_id,
            'tuition_fee_id' => $this->tuition_fee_id,
            'misc_fee_id' => $this->misc_fee_id,
            'other_fee_id' => $this->other_fee_id,
            'months' => $this->months,
            'current' => $this->current,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'tuition' => $this->whenLoaded('tuitionFee', function () {
                return new TuitionFeeResource($this->tuitionFee);
            }),
            'misc_fee' => $this->whenLoaded('miscFee', function () {
                return new MiscFeeResource($this->miscFee);
            }),
            'other_fees' => $this->whenLoaded('otherFees', function () {
                return TransactionOtherFeeResource::collection($this->otherFees);
            }, []),
        ];
    }
}
