<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Http\Resources\User\UserResource;
use Illuminate\Http\Request;

class UserResourceTest extends TestCase
{
    public function test_user_resource_casts_id_age_gender_to_integers()
    {
        $userArray = [
            'id' => '25',
            'username' => 'johndoe',
            'created_at' => '2026-08-16 00:00:00',
            'user' => [
                'email' => 'john@example.com',
                'first_name' => 'John',
                'middle_name' => 'D',
                'last_name' => 'Doe',
                'photo' => null,
                'p_address' => 'Address 1',
                'c_address' => 'Address 2',
                'birthdate' => '2000-01-01',
                'contact_number' => '123456789',
                'gender' => '1',
                'place_of_birth' => 'City',
                'age' => '18',
                'religion' => 'Catholic',
                'citizenship' => 'Filipino',
            ],
            'grade_level' => 'Grade 12',
            'section' => 'A',
            'school_year' => '2025-2026',
        ];

        $resource = new UserResource($userArray);
        $result = $resource->toArray(new Request());

        $this->assertIsInt($result['id']);
        $this->assertEquals(25, $result['id']);

        $this->assertIsInt($result['age']);
        $this->assertEquals(18, $result['age']);

        $this->assertIsInt($result['gender']);
        $this->assertEquals(1, $result['gender']);
    }
}
