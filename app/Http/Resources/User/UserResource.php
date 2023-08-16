<?php

namespace App\Http\Resources\User;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'id' => $this['id'],
            'username' => $this['username'],
            'email' => $this['user']['email'],
            'first_name' => $this['user']['first_name'],
            'middle_name' => $this['user']['middle_name'],
            'last_name' => $this['user']['last_name'],
            'photo' => config('app.env') === 'production' ? "https://sja-bataan.edu.ph/public/img/account/photo/{$this['user']['photo']}" : "http://localhost:8000/img/account/photo/{$this['user']['photo']}",
            'p_address' => $this['user']['p_address'],
            'c_address' => $this['user']['c_address'],
            'birthdate' => Carbon::createFromFormat('Y-m-d H:i:s', $this['user']['birthdate'])->format('Y-m-d') ?: '',
            'contact_number' => $this['user']['contact_number'],
            'gender' => $this['user']['gender'],
            'place_of_birth' => $this['user']['place_of_birth'],
            'age' => $this['user']['age'],
            'religion' => $this['user']['religion'],
            'citizenship' => $this['user']['citizenship'],
            'grade_level' => $request['grade_level'] ?? $this['grade_level'],
            'section' => $request['section'] ?? $this['section'],
            'school_year' => $request['school_year'] ?? $this['school_year'],
            'created_at' => $this['created_at'],
        ];
    }
}
