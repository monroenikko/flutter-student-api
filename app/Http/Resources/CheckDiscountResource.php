<?php

namespace App\Http\Resources;

use App\Models\TransactionDiscount;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckDiscountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return $this->map(function ($item) {
            $has_discount = TransactionDiscount::where('student_id', $item->student_information_id)
                ->where('school_year_id', $item->school_year_id)
                ->where('discount_type', $item->disc_type)
                ->where('isSuccess', 1)
                ->first();

            return [
                'has_discount' =>  isset($has_discount) ? true : false,
                'disc_type' =>  $item->disc_type,
                'disc_amt' =>  $item->disc_amt,
                'id' =>  $item->id
            ];
        });
    }
}
