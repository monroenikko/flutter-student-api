<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class StudentProfileRequest extends FormRequest
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
            'first_name' => 'required|string',
            'middle_name' => 'required|string',
            'last_name' => 'required|string',
            'c_address' => 'required|string',
            'birthdate' => 'required|string',
            'p_address' => 'required|string',
            'age' => 'required|string',
            'gender' => 'required|string',
            'birthdate' => 'required|string',
            'place_of_birth' => 'required|string',
            'religion' => 'required|string',
            'citizenship' => 'required|string',
            'image' => 'sometimes|image|mimes:jpg,png,jpeg,gif,svg|max:2048'
        ];
    }
}
