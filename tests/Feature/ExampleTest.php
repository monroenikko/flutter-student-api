<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_example()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_school_years_requires_auth()
    {
        $response = $this->getJson('/api/grade-sheets/school-years');

        $response->assertStatus(401);
    }

    public function test_grade_sheets_with_invalid_school_year_returns_404()
    {
        $user = new \App\Models\User();
        $user->username = 'testuser';
        $user->password = bcrypt('password');
        $user->save();

        $response = $this->actingAs($user)
            ->getJson('/api/grade-sheets?school_year_id=999');

        $response->assertStatus(404);
    }
}
