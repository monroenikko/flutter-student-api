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
            'id' => isset($this['id']) ? (is_numeric($this['id']) ? (int) $this['id'] : (string) $this['id']) : null,
            'username' => $this['username'] ?? null,
            'email' => $this['user']['email'] ?? null,
            'first_name' => $this['user']['first_name'] ?? null,
            'middle_name' => $this['user']['middle_name'] ?? null,
            'last_name' => $this['user']['last_name'] ?? null,
            'photo' => !empty($this['user']['photo']) ? ( config('app.env') === 'production' ? "https://sja-bataan.edu.ph/public/img/account/photo/{$this['user']['photo']}" : "https://sja-bataan.edu.ph/public/img/account/photo/blank-user.gif") : "https://sja-bataan.edu.ph/public/img/account/photo/blank-user.gif",
            'p_address' => $this['user']['p_address'] ?? null,
            'c_address' => $this['user']['c_address'] ?? null,
            'birthdate' => !empty($this['user']['birthdate'])
                ? Carbon::parse($this['user']['birthdate'])->format('Y-m-d')
                : '',
            'contact_number' => $this['user']['contact_number'] ?? null,
            'gender' => isset($this['user']['gender']) && $this['user']['gender'] !== '' && $this['user']['gender'] !== null ? (is_numeric($this['user']['gender']) ? (int) $this['user']['gender'] : (string) $this['user']['gender']) : null,
            'place_of_birth' => $this['user']['place_of_birth'] ?? null,
            'age' => isset($this['user']['age']) && $this['user']['age'] !== '' && $this['user']['age'] !== null ? (is_numeric($this['user']['age']) ? (int) $this['user']['age'] : (string) $this['user']['age']) : null,
            'religion' => $this['user']['religion'] ?? null,
            'citizenship' => $this['user']['citizenship'] ?? null,
            'grade_level' => $request['grade_level'] ?? $this['grade_level'] ?? null,
            'section' => $request['section'] ?? $this['section'] ?? null,
            'school_year' => $request['school_year'] ?? $this['school_year'] ?? null,
            'created_at' => $this['created_at'] ?? null,
        ];
    }
}
