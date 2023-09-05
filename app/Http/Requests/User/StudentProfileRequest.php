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
            'first_name' => 'required|string|max:191',
            'middle_name' => 'required|string|max:191',
            'last_name' => 'required|string|max:191',
            'c_address' => 'required|string|max:191',
            'birthdate' => 'required|string|max:191',
            'p_address' => 'required|string|max:191',
            'age' => 'required|string|max:10',
            'contact_number' => 'required|string|max:20',
            'gender' => 'required|string|max:50',
            'birthdate' => 'required|string|max:50',
            'place_of_birth' => 'required|string|max:191',
            'religion' => 'required|string|max:191',
            'citizenship' => 'required|string|max:191',
            'image' => 'sometimes|image|mimes:jpg,png,jpeg,gif,svg|max:2048'
        ];
    }
}
