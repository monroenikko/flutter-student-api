<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

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
        if (! \Illuminate\Support\Facades\Schema::hasTable('school_years')) {
            \Illuminate\Support\Facades\Schema::create('school_years', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->increments('id');
                $table->string('school_year')->nullable();
                $table->tinyInteger('current')->default(1);
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });
        }

        if (! \Illuminate\Support\Facades\Schema::hasTable('student_informations')) {
            \Illuminate\Support\Facades\Schema::create('student_informations', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('user_id')->nullable();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->timestamps();
            });
        }

        $user = \App\Models\User::factory()->create();
        $student = new \App\Models\StudentInformation();
        $student->user_id = $user->id;
        $student->first_name = 'Test';
        $student->last_name = 'User';
        $student->save();

        $response = $this->actingAs($user)
            ->getJson('/api/grade-sheets?school_year_id=999');

        $response->assertStatus(404);
    }
}
