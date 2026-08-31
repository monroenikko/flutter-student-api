<?php

namespace Tests\Feature;

use App\Models\ClassDetail;
use App\Models\ClassSubjectDetail;
use App\Models\Enrollment;
use App\Models\FacultyInformation;
use App\Models\Room;
use App\Models\SchoolYear;
use App\Models\SectionDetail;
use App\Models\StudentInformation;
use App\Models\SubjectDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class ClassScheduleApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestTables();
    }

    private function createTestTables(): void
    {
        if (! Schema::hasTable('school_years')) {
            Schema::create('school_years', function (Blueprint $table) {
                $table->increments('id');
                $table->string('school_year')->nullable();
                $table->tinyInteger('current')->default(1);
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('student_informations')) {
            Schema::create('student_informations', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('user_id')->nullable();
                $table->string('first_name')->nullable();
                $table->string('middle_name')->nullable();
                $table->string('last_name')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('section_details')) {
            Schema::create('section_details', function (Blueprint $table) {
                $table->increments('id');
                $table->string('section')->nullable();
                $table->integer('grade_level')->default(10);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('faculty_informations')) {
            Schema::create('faculty_informations', function (Blueprint $table) {
                $table->increments('id');
                $table->string('first_name')->nullable();
                $table->string('middle_name')->nullable();
                $table->string('last_name')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('rooms')) {
            Schema::create('rooms', function (Blueprint $table) {
                $table->increments('id');
                $table->string('room_code')->nullable();
                $table->string('room_description')->nullable();
                $table->tinyInteger('current')->default(1);
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('subject_details')) {
            Schema::create('subject_details', function (Blueprint $table) {
                $table->increments('id');
                $table->string('subject_code')->nullable();
                $table->string('subject')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('class_details')) {
            Schema::create('class_details', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('section_id')->nullable();
                $table->unsignedInteger('school_year_id')->nullable();
                $table->unsignedInteger('adviser_id')->nullable();
                $table->unsignedInteger('room_id')->nullable();
                $table->integer('grade_level')->default(10);
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('enrollments')) {
            Schema::create('enrollments', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('student_information_id')->nullable();
                $table->unsignedInteger('class_details_id')->nullable();
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('class_subject_details')) {
            Schema::create('class_subject_details', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('class_details_id')->nullable();
                $table->unsignedInteger('subject_id')->nullable();
                $table->unsignedInteger('faculty_id')->nullable();
                $table->unsignedInteger('room_id')->nullable();
                $table->string('class_days')->nullable();
                $table->string('class_schedule')->nullable();
                $table->time('class_time_from')->nullable();
                $table->time('class_time_to')->nullable();
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });
        }
    }

    public function test_unauthenticated_user_cannot_access_class_schedule(): void
    {
        $response = $this->getJson('/api/class-schedules');
        $response->assertStatus(401);
    }

    public function test_user_without_student_record_returns_404(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/class-schedules');

        $response->assertStatus(404)
            ->assertJson([
                'code'    => 404,
                'message' => 'Student information not found.',
            ]);
    }

    public function test_student_without_enrollment_returns_404(): void
    {
        $user = User::factory()->create();

        $student = new StudentInformation();
        $student->user_id = $user->id;
        $student->first_name = 'Maria';
        $student->last_name = 'Santos';
        $student->save();

        $sy = new SchoolYear();
        $sy->school_year = '2025-2026';
        $sy->current = 1;
        $sy->status = 1;
        $sy->save();

        $response = $this->actingAs($user)->getJson('/api/class-schedules');

        $response->assertStatus(404)
            ->assertJson([
                'code'    => 404,
                'message' => 'No class enrollment found for the selected school year.',
            ]);
    }

    public function test_student_can_retrieve_scheduled_subjects_for_current_school_year(): void
    {
        $user = User::factory()->create();

        $student = new StudentInformation();
        $student->user_id = $user->id;
        $student->first_name = 'Juan';
        $student->last_name = 'Dela Cruz';
        $student->save();

        $sy = new SchoolYear();
        $sy->school_year = '2025-2026';
        $sy->current = 1;
        $sy->status = 1;
        $sy->save();

        $section = new SectionDetail();
        $section->section = 'Grade 10 - St. Jude';
        $section->grade_level = 10;
        $section->save();

        $adviser = new FacultyInformation();
        $adviser->first_name = 'John';
        $adviser->last_name = 'Doe';
        $adviser->save();

        $room = new Room();
        $room->room_code = 'Room 101';
        $room->room_description = 'Grade 10 Classroom';
        $room->save();

        $classDetail = new ClassDetail();
        $classDetail->section_id = $section->id;
        $classDetail->school_year_id = $sy->id;
        $classDetail->adviser_id = $adviser->id;
        $classDetail->room_id = $room->id;
        $classDetail->grade_level = 10;
        $classDetail->status = 1;
        $classDetail->save();

        $enrollment = new Enrollment();
        $enrollment->student_information_id = $student->id;
        $enrollment->class_details_id = $classDetail->id;
        $enrollment->status = 1;
        $enrollment->save();

        $mathTeacher = new FacultyInformation();
        $mathTeacher->first_name = 'Aljon';
        $mathTeacher->last_name = 'Ebero';
        $mathTeacher->save();

        $mathSubject = new SubjectDetail();
        $mathSubject->subject_code = 'MATH10';
        $mathSubject->subject = 'Mathematics';
        $mathSubject->save();

        $classSubMath = new ClassSubjectDetail();
        $classSubMath->class_details_id = $classDetail->id;
        $classSubMath->subject_id = $mathSubject->id;
        $classSubMath->faculty_id = $mathTeacher->id;
        $classSubMath->room_id = $room->id;
        $classSubMath->class_days = 'M,T,W,TH,F';
        $classSubMath->class_time_from = '07:30:00';
        $classSubMath->class_time_to = '08:30:00';
        $classSubMath->status = 1;
        $classSubMath->save();

        $scienceTeacher = new FacultyInformation();
        $scienceTeacher->first_name = 'Maria';
        $scienceTeacher->last_name = 'Clara';
        $scienceTeacher->save();

        $scienceRoom = new Room();
        $scienceRoom->room_code = 'Lab A';
        $scienceRoom->room_description = 'Science Laboratory';
        $scienceRoom->save();

        $scienceSubject = new SubjectDetail();
        $scienceSubject->subject_code = 'SCI10';
        $scienceSubject->subject = 'Science';
        $scienceSubject->save();

        $classSubSci = new ClassSubjectDetail();
        $classSubSci->class_details_id = $classDetail->id;
        $classSubSci->subject_id = $scienceSubject->id;
        $classSubSci->faculty_id = $scienceTeacher->id;
        $classSubSci->room_id = $scienceRoom->id;
        $classSubSci->class_days = 'M,W,F';
        $classSubSci->class_time_from = '10:00:00';
        $classSubSci->class_time_to = '11:00:00';
        $classSubSci->status = 1;
        $classSubSci->save();

        $response = $this->actingAs($user)->getJson('/api/class-schedules');

        $response->assertOk()
            ->assertJson([
                'code'    => 200,
                'message' => 'Class schedule successfully fetched.',
            ])
            ->assertJsonPath('results.section', 'Grade 10 - St. Jude')
            ->assertJsonPath('results.grade_level', 10)
            ->assertJsonPath('results.school_year', '2025-2026')
            ->assertJsonPath('results.schedules_by_day.Mon.0.subject', 'Mathematics')
            ->assertJsonPath('results.schedules_by_day.Mon.0.faculty_name', 'Aljon Ebero')
            ->assertJsonPath('results.schedules_by_day.Mon.0.room', 'Room 101')
            ->assertJsonPath('results.schedules_by_day.Mon.0.time_display', '07:30 - 08:30')
            ->assertJsonPath('results.schedules_by_day.Mon.1.subject', 'Science')
            ->assertJsonPath('results.schedules_by_day.Mon.1.faculty_name', 'Maria Clara')
            ->assertJsonPath('results.schedules_by_day.Mon.1.room', 'Lab A')
            ->assertJsonPath('results.schedules_by_day.Mon.1.time_display', '10:00 - 11:00')
            ->assertJsonPath('results.schedules_by_day.Tue.0.subject', 'Mathematics')
            ->assertJsonPath('results.schedules_by_day.Tue.0.time_display', '07:30 - 08:30');
    }
}
