<?php

namespace Tests\Feature;

use App\Models\ClassDetail;
use App\Models\Enrollment;
use App\Models\FacultyInformation;
use App\Models\SchoolYear;
use App\Models\SectionDetail;
use App\Models\StudentAttendance;
use App\Models\StudentInformation;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ClassDetailApiTest extends TestCase
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

        if (! Schema::hasTable('student_attendances')) {
            Schema::create('student_attendances', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('school_year_id')->nullable();
                $table->text('junior_months_header')->nullable();
                $table->text('senior1_months_header')->nullable();
                $table->text('senior2_months_header')->nullable();
                $table->text('senior3_months_header')->nullable();
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

        if (! Schema::hasTable('class_details')) {
            Schema::create('class_details', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('section_id')->nullable();
                $table->unsignedInteger('school_year_id')->nullable();
                $table->unsignedInteger('adviser_id')->nullable();
                $table->unsignedInteger('room_id')->nullable();
                $table->integer('grade_level')->default(10);
                $table->string('term_type', 20)->default('old');
                $table->unsignedInteger('strand_id')->nullable();
                $table->tinyInteger('current')->default(1);
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('enrollments')) {
            Schema::create('enrollments', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('student_information_id')->nullable();
                $table->unsignedInteger('class_details_id')->nullable();
                $table->text('attendance')->nullable();
                $table->text('attendance_first')->nullable();
                $table->text('attendance_second')->nullable();
                $table->text('attendance_third')->nullable();
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('student_enrolled_subjects')) {
            Schema::create('student_enrolled_subjects', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('enrollments_id')->nullable();
                $table->unsignedInteger('class_subject_details_id')->nullable();
                $table->unsignedInteger('subject_id')->nullable();
                $table->unsignedInteger('sub_subject_id')->nullable();
                $table->decimal('fir_g', 5, 2)->default(0);
                $table->decimal('sec_g', 5, 2)->default(0);
                $table->decimal('thi_g', 5, 2)->default(0);
                $table->decimal('fou_g', 5, 2)->default(0);
                $table->integer('sem')->nullable();
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
                $table->unsignedInteger('subject_category_id')->nullable();
                $table->integer('class_subject_order')->default(1);
                $table->integer('sem')->nullable();
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('subject_details')) {
            Schema::create('subject_details', function (Blueprint $table) {
                $table->increments('id');
                $table->string('subject_code')->nullable();
                $table->string('subject')->nullable();
                $table->unsignedInteger('subject_category_id')->nullable();
                $table->timestamps();
            });
        }
    }

    public function test_unauthenticated_user_cannot_access_class_details(): void
    {
        $response = $this->getJson('/api/class-details');
        $response->assertStatus(401);
    }

    public function test_class_details_with_term_type_new_merges_three_terms_for_junior_high(): void
    {
        $user = User::factory()->create();

        $student = new StudentInformation();
        $student->user_id = $user->id;
        $student->first_name = 'Maria';
        $student->last_name = 'Santos';
        $student->save();

        $sy = new SchoolYear();
        $sy->school_year = '2026-2027';
        $sy->current = 1;
        $sy->status = 1;
        $sy->save();

        $attendanceHeader = new StudentAttendance();
        $attendanceHeader->school_year_id = $sy->id;
        $attendanceHeader->senior1_months_header = json_encode(['key' => ['Aug', 'Sep', 'Oct']]);
        $attendanceHeader->senior2_months_header = json_encode(['key' => ['Nov', 'Dec', 'Jan']]);
        $attendanceHeader->senior3_months_header = json_encode(['key' => ['Feb', 'Mar', 'Apr']]);
        $attendanceHeader->save();

        $section = new SectionDetail();
        $section->section = 'Grade 8 - St. Luke';
        $section->grade_level = 8;
        $section->save();

        $adviser = new FacultyInformation();
        $adviser->first_name = 'Juan';
        $adviser->last_name = 'Dela Cruz';
        $adviser->save();

        $classDetail = new ClassDetail();
        $classDetail->section_id = $section->id;
        $classDetail->school_year_id = $sy->id;
        $classDetail->adviser_id = $adviser->id;
        $classDetail->grade_level = 8; // Junior High
        $classDetail->term_type = 'new';
        $classDetail->status = 1;
        $classDetail->save();

        $enrollment = new Enrollment();
        $enrollment->student_information_id = $student->id;
        $enrollment->class_details_id = $classDetail->id;
        $enrollment->attendance_first = json_encode([
            'days_of_school' => [20, 21, 22],
            'days_present'   => [19, 20, 21],
            'days_absent'    => [1, 1, 1],
            'times_tardy'    => [0, 1, 0],
        ]);
        $enrollment->attendance_second = json_encode([
            'days_of_school' => [18, 15, 20],
            'days_present'   => [18, 14, 20],
            'days_absent'    => [0, 1, 0],
            'times_tardy'    => [1, 0, 0],
        ]);
        $enrollment->attendance_third = json_encode([
            'days_of_school' => [19, 21, 18],
            'days_present'   => [19, 20, 17],
            'days_absent'    => [0, 1, 1],
            'times_tardy'    => [0, 0, 0],
        ]);
        $enrollment->status = 1;
        $enrollment->save();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/class-details');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'code',
                'results' => [
                    'class_details_id',
                    'section',
                    'grade_level',
                    'term_type',
                    'adviser',
                    'attendance' => [
                        'table_header' => ['key'],
                        'attendance' => [
                            'days_of_school',
                            'days_present',
                            'days_absent',
                            'times_tardy',
                        ],
                        'days_of_school_total',
                        'days_present_total',
                        'days_absent_total',
                        'times_tardy_total',
                    ],
                ],
            ]);

        $data = $response->json('results');
        $this->assertEquals('new', $data['term_type']);
        $this->assertEquals(8, $data['grade_level']);

        $expectedHeader = ['Aug', 'Sep', 'Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar', 'Apr', 'Total'];
        $this->assertEquals($expectedHeader, $data['attendance']['table_header']['key']);

        // Check merged totals
        // days_of_school: [20, 21, 22, 18, 15, 20, 19, 21, 18] = sum is 174
        $this->assertEquals(174, $data['attendance']['days_of_school_total']);
        // days_present: [19, 20, 21, 18, 14, 20, 19, 20, 17] = sum is 168
        $this->assertEquals(168, $data['attendance']['days_present_total']);
        // days_absent: [1, 1, 1, 0, 1, 0, 0, 1, 1] = sum is 6
        $this->assertEquals(6, $data['attendance']['days_absent_total']);
        // times_tardy: [0, 1, 0, 1, 0, 0, 0, 0, 0] = sum is 2
        $this->assertEquals(2, $data['attendance']['times_tardy_total']);
    }

    public function test_class_details_with_term_type_new_merges_three_terms_for_senior_high(): void
    {
        $user = User::factory()->create();

        $student = new StudentInformation();
        $student->user_id = $user->id;
        $student->first_name = 'Angelo';
        $student->last_name = 'Reyes';
        $student->save();

        $sy = new SchoolYear();
        $sy->school_year = '2026-2027';
        $sy->current = 1;
        $sy->status = 1;
        $sy->save();

        $attendanceHeader = new StudentAttendance();
        $attendanceHeader->school_year_id = $sy->id;
        $attendanceHeader->senior1_months_header = json_encode(['key' => ['Aug', 'Sep', 'Oct']]);
        $attendanceHeader->senior2_months_header = json_encode(['key' => ['Nov', 'Dec', 'Jan']]);
        $attendanceHeader->senior3_months_header = json_encode(['key' => ['Feb', 'Mar', 'Apr']]);
        $attendanceHeader->save();

        $section = new SectionDetail();
        $section->section = 'Grade 11 - STEM A';
        $section->grade_level = 11;
        $section->save();

        $adviser = new FacultyInformation();
        $adviser->first_name = 'Maria';
        $adviser->last_name = 'Lopez';
        $adviser->save();

        $classDetail = new ClassDetail();
        $classDetail->section_id = $section->id;
        $classDetail->school_year_id = $sy->id;
        $classDetail->adviser_id = $adviser->id;
        $classDetail->grade_level = 11; // Senior High
        $classDetail->term_type = 'new';
        $classDetail->status = 1;
        $classDetail->save();

        $enrollment = new Enrollment();
        $enrollment->student_information_id = $student->id;
        $enrollment->class_details_id = $classDetail->id;
        $enrollment->attendance_first = json_encode([
            'days_of_school' => [22, 20, 21],
            'days_present'   => [22, 19, 21],
            'days_absent'    => [0, 1, 0],
            'times_tardy'    => [0, 0, 1],
        ]);
        $enrollment->attendance_second = json_encode([
            'days_of_school' => [19, 16, 21],
            'days_present'   => [18, 16, 20],
            'days_absent'    => [1, 0, 1],
            'times_tardy'    => [0, 1, 0],
        ]);
        $enrollment->attendance_third = json_encode([
            'days_of_school' => [20, 22, 19],
            'days_present'   => [20, 21, 19],
            'days_absent'    => [0, 1, 0],
            'times_tardy'    => [0, 0, 0],
        ]);
        $enrollment->status = 1;
        $enrollment->save();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/class-details');

        $response->assertStatus(200);
        $data = $response->json('results');

        $this->assertEquals('new', $data['term_type']);
        $this->assertEquals(11, $data['grade_level']);
        $this->assertEquals(180, $data['attendance']['days_of_school_total']);
        $this->assertEquals(176, $data['attendance']['days_present_total']);
        $this->assertEquals(4, $data['attendance']['days_absent_total']);
        $this->assertEquals(2, $data['attendance']['times_tardy_total']);
    }

    public function test_class_details_with_term_type_old_preserves_legacy_structure(): void
    {
        $user = User::factory()->create();

        $student = new StudentInformation();
        $student->user_id = $user->id;
        $student->first_name = 'Juan';
        $student->last_name = 'Cruz';
        $student->save();

        $sy = new SchoolYear();
        $sy->school_year = '2025-2026';
        $sy->current = 1;
        $sy->status = 1;
        $sy->save();

        $attendanceHeader = new StudentAttendance();
        $attendanceHeader->school_year_id = $sy->id;
        $attendanceHeader->junior_months_header = json_encode(['key' => ['Jun', 'Jul', 'Aug']]);
        $attendanceHeader->senior1_months_header = json_encode(['key' => ['Jun', 'Jul']]);
        $attendanceHeader->senior2_months_header = json_encode(['key' => ['Nov', 'Dec']]);
        $attendanceHeader->save();

        $section = new SectionDetail();
        $section->section = 'Grade 10 - Rizal';
        $section->grade_level = 10;
        $section->save();

        $adviser = new FacultyInformation();
        $adviser->first_name = 'Pedro';
        $adviser->last_name = 'Gomez';
        $adviser->save();

        $classDetail = new ClassDetail();
        $classDetail->section_id = $section->id;
        $classDetail->school_year_id = $sy->id;
        $classDetail->adviser_id = $adviser->id;
        $classDetail->grade_level = 10;
        $classDetail->term_type = 'old';
        $classDetail->status = 1;
        $classDetail->save();

        $enrollment = new Enrollment();
        $enrollment->student_information_id = $student->id;
        $enrollment->class_details_id = $classDetail->id;
        $enrollment->attendance = json_encode([
            'days_of_school' => [20, 22, 21],
            'days_present'   => [20, 21, 20],
            'days_absent'    => [0, 1, 1],
            'times_tardy'    => [0, 0, 1],
        ]);
        $enrollment->attendance_first = json_encode([
            'days_of_school' => [20, 22],
            'days_present'   => [20, 21],
            'days_absent'    => [0, 1],
            'times_tardy'    => [0, 0],
        ]);
        $enrollment->attendance_second = json_encode([
            'days_of_school' => [18, 15],
            'days_present'   => [18, 14],
            'days_absent'    => [0, 1],
            'times_tardy'    => [1, 0],
        ]);
        $enrollment->status = 1;
        $enrollment->save();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/class-details');

        $response->assertStatus(200);
        $data = $response->json('results');

        $this->assertEquals('old', $data['term_type']);
        $this->assertArrayHasKey('attendance_junior', $data);
        $this->assertArrayHasKey('attendance_senior1', $data);
        $this->assertArrayHasKey('attendance_senior2', $data);
        $this->assertEquals(63, $data['attendance_junior']['days_of_school_total']);
    }

    public function test_class_details_with_term_type_old_senior_high_preserves_senior_attendance(): void
    {
        $user = User::factory()->create();

        $student = new StudentInformation();
        $student->user_id = $user->id;
        $student->first_name = 'Senior';
        $student->last_name = 'Student';
        $student->save();

        $sy = new SchoolYear();
        $sy->school_year = '2025-2026';
        $sy->current = 1;
        $sy->status = 1;
        $sy->save();

        $attendanceHeader = new StudentAttendance();
        $attendanceHeader->school_year_id = $sy->id;
        $attendanceHeader->senior1_months_header = json_encode(['key' => ['Jun', 'Jul', 'Aug']]);
        $attendanceHeader->senior2_months_header = json_encode(['key' => ['Nov', 'Dec', 'Jan']]);
        $attendanceHeader->save();

        $section = new SectionDetail();
        $section->section = 'Grade 12 - TVL';
        $section->grade_level = 12;
        $section->save();

        $adviser = new FacultyInformation();
        $adviser->first_name = 'Elena';
        $adviser->last_name = 'Santos';
        $adviser->save();

        $classDetail = new ClassDetail();
        $classDetail->section_id = $section->id;
        $classDetail->school_year_id = $sy->id;
        $classDetail->adviser_id = $adviser->id;
        $classDetail->grade_level = 12;
        $classDetail->term_type = 'old';
        $classDetail->status = 1;
        $classDetail->save();

        $enrollment = new Enrollment();
        $enrollment->student_information_id = $student->id;
        $enrollment->class_details_id = $classDetail->id;
        $enrollment->attendance_first = json_encode([
            'days_of_school' => [20, 22, 21],
            'days_present'   => [20, 21, 20],
            'days_absent'    => [0, 1, 1],
            'times_tardy'    => [0, 0, 1],
        ]);
        $enrollment->attendance_second = json_encode([
            'days_of_school' => [18, 15, 20],
            'days_present'   => [18, 14, 19],
            'days_absent'    => [0, 1, 1],
            'times_tardy'    => [1, 0, 0],
        ]);
        $enrollment->status = 1;
        $enrollment->save();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/class-details');

        $response->assertStatus(200);
        $data = $response->json('results');

        $this->assertEquals('old', $data['term_type']);
        $this->assertEquals(12, $data['grade_level']);
        $this->assertArrayHasKey('attendance_senior1', $data);
        $this->assertArrayHasKey('attendance_senior2', $data);
        $this->assertEquals(63, $data['attendance_senior1']['days_of_school_total']);
        $this->assertEquals(61, $data['attendance_senior1']['days_present_total']);
        $this->assertEquals(53, $data['attendance_senior2']['days_of_school_total']);
        $this->assertEquals(51, $data['attendance_senior2']['days_present_total']);
    }
}
