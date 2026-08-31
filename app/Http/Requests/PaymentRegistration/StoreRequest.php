<?php

namespace App\Http\Requests\PaymentRegistration;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'payment_category_id' => ['sometimes', 'nullable'],
            'or_no' => ['required', 'string'],
            'payment_fee' => ['required', 'numeric'],
            'school_year_id' => ['required', 'integer'],
            'email' => ['required', 'email'],
            'payment_option' => ['required', 'string'],
            'receipt_image' => ['required', 'file', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:10240'],
            'downpayment' => ['sometimes', 'nullable'],
            'discount' => ['sometimes', 'array'],
            'discount.*' => ['integer'],
        ];
    }
}
