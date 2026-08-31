<?php

namespace Tests\Feature;

use App\Models\ClassDetail;
use App\Models\ClassSubjectDetail;
use App\Models\Enrollment;
use App\Models\FacultyInformation;
use App\Models\GradeReleaseSchedule;
use App\Models\SchoolYear;
use App\Models\SectionDetail;
use App\Models\StudentEnrolledSubject;
use App\Models\StudentInformation;
use App\Models\SubjectCategory;
use App\Models\SubjectDetail;
use App\Models\SubSubjectDetail;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GradeSheetApiTest extends TestCase
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

        if (! Schema::hasTable('subject_categories')) {
            Schema::create('subject_categories', function (Blueprint $table) {
                $table->increments('id');
                $table->string('code', 50)->nullable();
                $table->string('name')->nullable();
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

        if (! Schema::hasTable('sub_subject_details')) {
            Schema::create('sub_subject_details', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('subject_details_id')->nullable();
                $table->string('sub_subject_code', 15)->nullable();
                $table->string('sub_subject')->nullable();
                $table->tinyInteger('units')->default(1);
                $table->tinyInteger('current')->default(1);
                $table->tinyInteger('status')->default(1);
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

        if (! Schema::hasTable('grade_release_schedules')) {
            Schema::create('grade_release_schedules', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('school_year_id');
                $table->string('term_type', 20)->default('new');
                $table->tinyInteger('term')->default(1);
                $table->tinyInteger('is_enabled')->default(0);
                $table->dateTime('scheduled_at')->nullable();
                $table->dateTime('published_at')->nullable();
                $table->unsignedBigInteger('published_by')->nullable();
                $table->string('status', 20)->default('draft');
                $table->timestamps();
            });
        }
    }

    public function test_unauthenticated_user_cannot_access_grade_sheets(): void
    {
        $response = $this->getJson('/api/grade-sheets');
        $response->assertStatus(401);
    }

    public function test_grade_sheets_with_term_type_new_returns_three_terms_and_correct_final_grade(): void
    {
        $user = User::factory()->create();

        $student = new StudentInformation();
        $student->user_id = $user->id;
        $student->first_name = 'John';
        $student->middle_name = 'M';
        $student->last_name = 'Doe';
        $student->save();

        $sy = new SchoolYear();
        $sy->school_year = '2025-2026';
        $sy->current = 1;
        $sy->status = 1;
        $sy->save();

        $section = new SectionDetail();
        $section->section = 'St. Therese';
        $section->grade_level = 7;
        $section->save();

        $adviser = new FacultyInformation();
        $adviser->first_name = 'Maria';
        $adviser->middle_name = 'C';
        $adviser->last_name = 'Santos';
        $adviser->save();

        $classDetail = new ClassDetail();
        $classDetail->section_id = $section->id;
        $classDetail->school_year_id = $sy->id;
        $classDetail->adviser_id = $adviser->id;
        $classDetail->grade_level = 7;
        $classDetail->term_type = 'new';
        $classDetail->status = 1;
        $classDetail->save();

        $enrollment = new Enrollment();
        $enrollment->student_information_id = $student->id;
        $enrollment->class_details_id = $classDetail->id;
        $enrollment->status = 1;
        $enrollment->save();

        $subject = new SubjectDetail();
        $subject->subject_code = 'MATH7';
        $subject->subject = 'Mathematics 7';
        $subject->save();

        $classSubject = new ClassSubjectDetail();
        $classSubject->class_details_id = $classDetail->id;
        $classSubject->subject_id = $subject->id;
        $classSubject->faculty_id = $adviser->id;
        $classSubject->class_subject_order = 1;
        $classSubject->status = 1;
        $classSubject->save();

        $enrolledSubject = new StudentEnrolledSubject();
        $enrolledSubject->enrollments_id = $enrollment->id;
        $enrolledSubject->class_subject_details_id = $classSubject->id;
        $enrolledSubject->subject_id = $subject->id;
        $enrolledSubject->fir_g = 88;
        $enrolledSubject->sec_g = 90;
        $enrolledSubject->thi_g = 92;
        $enrolledSubject->fou_g = 0;
        $enrolledSubject->status = 1;
        $enrolledSubject->save();

        $response = $this->actingAs($user)->getJson('/api/grade-sheets');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Data successfully listed.',
                'code'    => 200,
                'results' => [
                    'section'     => 'St. Therese',
                    'grade_level' => 7,
                    'term_type'   => 'new',
                    'grades'      => [
                        [
                            'subject'        => 'Mathematics 7',
                            'subject_code'   => 'MATH7',
                            'is_sub_subject' => false,
                            'is_elective'    => false,
                            'fir_g'          => 88,
                            'sec_g'          => 90,
                            'thi_g'          => 92,
                            'final_g'        => 90,
                            'order'          => 1,
                            'status'         => 1,
                        ]
                    ]
                ]
            ]);

        $responseData = $response->json('results');
        $this->assertEquals('new', $responseData['term_type']);
        $firstGrade = $responseData['grades'][0];
        $this->assertArrayNotHasKey('fou_g', $firstGrade);
        $this->assertEquals(90, $firstGrade['final_g']);
    }

    public function test_grade_sheets_displays_sub_subject_title_and_indention_flag(): void
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
        $section->section = 'St. Jude';
        $section->grade_level = 7;
        $section->save();

        $adviser = new FacultyInformation();
        $adviser->first_name = 'Teacher';
        $adviser->last_name = 'One';
        $adviser->save();

        $classDetail = new ClassDetail();
        $classDetail->section_id = $section->id;
        $classDetail->school_year_id = $sy->id;
        $classDetail->adviser_id = $adviser->id;
        $classDetail->grade_level = 7;
        $classDetail->term_type = 'new';
        $classDetail->status = 1;
        $classDetail->save();

        $enrollment = new Enrollment();
        $enrollment->student_information_id = $student->id;
        $enrollment->class_details_id = $classDetail->id;
        $enrollment->status = 1;
        $enrollment->save();

        $mapehSubject = new SubjectDetail();
        $mapehSubject->subject_code = 'MAPEH7';
        $mapehSubject->subject = 'MAPEH';
        $mapehSubject->save();

        $musicSubSubject = new SubSubjectDetail();
        $musicSubSubject->subject_details_id = $mapehSubject->id;
        $musicSubSubject->sub_subject_code = 'MUS7';
        $musicSubSubject->sub_subject = 'MUSIC';
        $musicSubSubject->units = 1;
        $musicSubSubject->status = 1;
        $musicSubSubject->save();

        $classSubject = new ClassSubjectDetail();
        $classSubject->class_details_id = $classDetail->id;
        $classSubject->subject_id = $mapehSubject->id;
        $classSubject->faculty_id = $adviser->id;
        $classSubject->class_subject_order = 1;
        $classSubject->status = 1;
        $classSubject->save();

        $enrolledSubject = new StudentEnrolledSubject();
        $enrolledSubject->enrollments_id = $enrollment->id;
        $enrolledSubject->class_subject_details_id = $classSubject->id;
        $enrolledSubject->subject_id = $mapehSubject->id;
        $enrolledSubject->sub_subject_id = $musicSubSubject->id;
        $enrolledSubject->fir_g = 85;
        $enrolledSubject->sec_g = 87;
        $enrolledSubject->thi_g = 89;
        $enrolledSubject->status = 1;
        $enrolledSubject->save();

        $response = $this->actingAs($user)->getJson('/api/grade-sheets');

        $response->assertStatus(200);
        $responseData = $response->json('results');
        $firstGrade = $responseData['grades'][0];

        $this->assertEquals('Music', $firstGrade['subject']);
        $this->assertEquals('MUS7', $firstGrade['subject_code']);
        $this->assertTrue($firstGrade['is_sub_subject']);
        $this->assertEquals(87, $firstGrade['final_g']);
    }

    public function test_grade_sheets_with_elective_subject_in_senior_level_new_term(): void
    {
        $user = User::factory()->create();

        $student = new StudentInformation();
        $student->user_id = $user->id;
        $student->first_name = 'Senior';
        $student->last_name = 'ElectiveStudent';
        $student->save();

        $sy = new SchoolYear();
        $sy->school_year = '2025-2026';
        $sy->current = 1;
        $sy->status = 1;
        $sy->save();

        $section = new SectionDetail();
        $section->section = 'Grade 11 - STEM';
        $section->grade_level = 11;
        $section->save();

        $adviser = new FacultyInformation();
        $adviser->first_name = 'Prof';
        $adviser->last_name = 'Elective';
        $adviser->save();

        $classDetail = new ClassDetail();
        $classDetail->section_id = $section->id;
        $classDetail->school_year_id = $sy->id;
        $classDetail->adviser_id = $adviser->id;
        $classDetail->grade_level = 11;
        $classDetail->term_type = 'new';
        $classDetail->status = 1;
        $classDetail->save();

        $enrollment = new Enrollment();
        $enrollment->student_information_id = $student->id;
        $enrollment->class_details_id = $classDetail->id;
        $enrollment->status = 1;
        $enrollment->save();

        $electiveCategory = new SubjectCategory();
        $electiveCategory->code = 'elective';
        $electiveCategory->name = 'Elective Subjects';
        $electiveCategory->status = 1;
        $electiveCategory->save();

        $subject = new SubjectDetail();
        $subject->subject_code = 'ROBOTICS';
        $subject->subject = 'Robotics and Automation';
        $subject->subject_category_id = $electiveCategory->id;
        $subject->save();

        $classSubject = new ClassSubjectDetail();
        $classSubject->class_details_id = $classDetail->id;
        $classSubject->subject_id = $subject->id;
        $classSubject->faculty_id = $adviser->id;
        $classSubject->subject_category_id = $electiveCategory->id;
        $classSubject->class_subject_order = 1;
        $classSubject->sem = 2; // Taken in 2nd term
        $classSubject->status = 1;
        $classSubject->save();

        $enrolledSubject = new StudentEnrolledSubject();
        $enrolledSubject->enrollments_id = $enrollment->id;
        $enrolledSubject->class_subject_details_id = $classSubject->id;
        $enrolledSubject->subject_id = $subject->id;
        $enrolledSubject->fir_g = 0;
        $enrolledSubject->sec_g = 94; // 2nd term grade
        $enrolledSubject->thi_g = 0;
        $enrolledSubject->sem = 2;
        $enrolledSubject->status = 1;
        $enrolledSubject->save();

        $response = $this->actingAs($user)->getJson('/api/grade-sheets');

        $response->assertStatus(200);
        $responseData = $response->json('results');
        $firstGrade = $responseData['grades'][0];

        $this->assertTrue($firstGrade['is_elective']);
        $this->assertEquals(2, $firstGrade['term']);
        $this->assertEquals(94, $firstGrade['sec_g']);
        $this->assertEquals(94, $firstGrade['final_g']);
    }

    public function test_grade_sheets_with_term_type_old_junior_high_returns_four_quarters(): void
    {
        $user = User::factory()->create();

        $student = new StudentInformation();
        $student->user_id = $user->id;
        $student->first_name = 'Jane';
        $student->middle_name = 'A';
        $student->last_name = 'Smith';
        $student->save();

        $sy = new SchoolYear();
        $sy->school_year = '2025-2026';
        $sy->current = 1;
        $sy->status = 1;
        $sy->save();

        $section = new SectionDetail();
        $section->section = 'St. Jude';
        $section->grade_level = 10;
        $section->save();

        $adviser = new FacultyInformation();
        $adviser->first_name = 'Carlos';
        $adviser->middle_name = 'B';
        $adviser->last_name = 'Reyes';
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
        $enrollment->status = 1;
        $enrollment->save();

        $subject = new SubjectDetail();
        $subject->subject_code = 'SCI10';
        $subject->subject = 'Science 10';
        $subject->save();

        $classSubject = new ClassSubjectDetail();
        $classSubject->class_details_id = $classDetail->id;
        $classSubject->subject_id = $subject->id;
        $classSubject->faculty_id = $adviser->id;
        $classSubject->class_subject_order = 1;
        $classSubject->status = 1;
        $classSubject->save();

        $enrolledSubject = new StudentEnrolledSubject();
        $enrolledSubject->enrollments_id = $enrollment->id;
        $enrolledSubject->class_subject_details_id = $classSubject->id;
        $enrolledSubject->subject_id = $subject->id;
        $enrolledSubject->fir_g = 80;
        $enrolledSubject->sec_g = 85;
        $enrolledSubject->thi_g = 90;
        $enrolledSubject->fou_g = 95;
        $enrolledSubject->status = 1;
        $enrolledSubject->save();

        $response = $this->actingAs($user)->getJson('/api/grade-sheets');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Data successfully listed.',
                'code'    => 200,
                'results' => [
                    'section'     => 'St. Jude',
                    'grade_level' => 10,
                    'term_type'   => 'old',
                    'grades'      => [
                        [
                            'subject'      => 'Science 10',
                            'subject_code' => 'SCI10',
                            'fir_g'        => 80,
                            'sec_g'        => 85,
                            'thi_g'        => 90,
                            'fou_g'        => 95,
                            'final_g'      => 88,
                            'order'        => 1,
                            'status'       => 1,
                        ]
                    ]
                ]
            ]);

        $responseData = $response->json('results');
        $this->assertEquals('old', $responseData['term_type']);
        $firstGrade = $responseData['grades'][0];
        $this->assertArrayHasKey('fou_g', $firstGrade);
        $this->assertEquals(95, $firstGrade['fou_g']);
        $this->assertEquals(88, $firstGrade['final_g']);
    }

    public function test_grade_sheets_with_term_type_old_senior_high_returns_semesters(): void
    {
        $user = User::factory()->create();

        $student = new StudentInformation();
        $student->user_id = $user->id;
        $student->first_name = 'Senior';
        $student->middle_name = 'S';
        $student->last_name = 'Student';
        $student->save();

        $sy = new SchoolYear();
        $sy->school_year = '2025-2026';
        $sy->current = 1;
        $sy->status = 1;
        $sy->save();

        $section = new SectionDetail();
        $section->section = 'Grade 11 - STEM';
        $section->grade_level = 11;
        $section->save();

        $adviser = new FacultyInformation();
        $adviser->first_name = 'Prof';
        $adviser->middle_name = 'P';
        $adviser->last_name = 'Smith';
        $adviser->save();

        $classDetail = new ClassDetail();
        $classDetail->section_id = $section->id;
        $classDetail->school_year_id = $sy->id;
        $classDetail->adviser_id = $adviser->id;
        $classDetail->grade_level = 11;
        $classDetail->term_type = 'old';
        $classDetail->status = 1;
        $classDetail->save();

        $enrollment = new Enrollment();
        $enrollment->student_information_id = $student->id;
        $enrollment->class_details_id = $classDetail->id;
        $enrollment->status = 1;
        $enrollment->save();

        $subject1 = new SubjectDetail();
        $subject1->subject_code = 'PRECAL';
        $subject1->subject = 'Pre-Calculus';
        $subject1->save();

        $classSubject1 = new ClassSubjectDetail();
        $classSubject1->class_details_id = $classDetail->id;
        $classSubject1->subject_id = $subject1->id;
        $classSubject1->faculty_id = $adviser->id;
        $classSubject1->class_subject_order = 1;
        $classSubject1->sem = 1;
        $classSubject1->status = 1;
        $classSubject1->save();

        $enrolledSubject = new StudentEnrolledSubject();
        $enrolledSubject->enrollments_id = $enrollment->id;
        $enrolledSubject->class_subject_details_id = $classSubject1->id;
        $enrolledSubject->subject_id = $subject1->id;
        $enrolledSubject->fir_g = 90;
        $enrolledSubject->sec_g = 92;
        $enrolledSubject->sem = 1;
        $enrolledSubject->status = 1;
        $enrolledSubject->save();

        $response = $this->actingAs($user)->getJson('/api/grade-sheets');

        $response->assertStatus(200);
        $data = $response->json('results');
        $this->assertEquals('old', $data['term_type']);
        $this->assertArrayHasKey('first_sem', $data);
        $this->assertArrayHasKey('second_sem', $data);
    }

    public function test_grade_sheets_with_term_type_new_senior_high_regular_subject_returns_three_terms(): void
    {
        $user = User::factory()->create();

        $student = new StudentInformation();
        $student->user_id = $user->id;
        $student->first_name = 'Senior';
        $student->last_name = 'RegularStudent';
        $student->save();

        $sy = new SchoolYear();
        $sy->school_year = '2026-2027';
        $sy->current = 1;
        $sy->status = 1;
        $sy->save();

        $section = new SectionDetail();
        $section->section = 'Grade 12 - STEM';
        $section->grade_level = 12;
        $section->save();

        $adviser = new FacultyInformation();
        $adviser->first_name = 'Prof';
        $adviser->last_name = 'Science';
        $adviser->save();

        $classDetail = new ClassDetail();
        $classDetail->section_id = $section->id;
        $classDetail->school_year_id = $sy->id;
        $classDetail->adviser_id = $adviser->id;
        $classDetail->grade_level = 12;
        $classDetail->term_type = 'new';
        $classDetail->status = 1;
        $classDetail->save();

        $enrollment = new Enrollment();
        $enrollment->student_information_id = $student->id;
        $enrollment->class_details_id = $classDetail->id;
        $enrollment->status = 1;
        $enrollment->save();

        $subject = new SubjectDetail();
        $subject->subject_code = 'GENBIO';
        $subject->subject = 'General Biology';
        $subject->save();

        $classSubject = new ClassSubjectDetail();
        $classSubject->class_details_id = $classDetail->id;
        $classSubject->subject_id = $subject->id;
        $classSubject->faculty_id = $adviser->id;
        $classSubject->class_subject_order = 1;
        $classSubject->status = 1;
        $classSubject->save();

        $enrolledSubject = new StudentEnrolledSubject();
        $enrolledSubject->enrollments_id = $enrollment->id;
        $enrolledSubject->class_subject_details_id = $classSubject->id;
        $enrolledSubject->subject_id = $subject->id;
        $enrolledSubject->fir_g = 88;
        $enrolledSubject->sec_g = 91;
        $enrolledSubject->thi_g = 94;
        $enrolledSubject->fou_g = 0;
        $enrolledSubject->status = 1;
        $enrolledSubject->save();

        $response = $this->actingAs($user)->getJson('/api/grade-sheets');

        $response->assertStatus(200);
        $responseData = $response->json('results');

        $this->assertEquals('new', $responseData['term_type']);
        $this->assertEquals(12, $responseData['grade_level']);
        $this->assertArrayNotHasKey('first_sem', $responseData);
        $this->assertArrayNotHasKey('second_sem', $responseData);
        $this->assertArrayHasKey('grades', $responseData);

        $firstGrade = $responseData['grades'][0];
        $this->assertEquals('General Biology', $firstGrade['subject']);
        $this->assertEquals(88, $firstGrade['fir_g']);
        $this->assertEquals(91, $firstGrade['sec_g']);
        $this->assertEquals(94, $firstGrade['thi_g']);
        $this->assertArrayNotHasKey('fou_g', $firstGrade);
        $this->assertEquals(91, $firstGrade['final_g']);
    }

    public function test_grade_sheets_includes_grade_release_schedules_and_reveals_only_published_terms(): void
    {
        $user = User::factory()->create();

        $student = new StudentInformation();
        $student->user_id = $user->id;
        $student->first_name = 'Schedule';
        $student->last_name = 'Student';
        $student->save();

        $sy = new SchoolYear();
        $sy->school_year = '2026-2027';
        $sy->current = 1;
        $sy->status = 1;
        $sy->save();

        $section = new SectionDetail();
        $section->section = 'Grade 8 - Hope';
        $section->grade_level = 8;
        $section->save();

        $adviser = new FacultyInformation();
        $adviser->first_name = 'Adviser';
        $adviser->last_name = 'Teacher';
        $adviser->save();

        $classDetail = new ClassDetail();
        $classDetail->section_id = $section->id;
        $classDetail->school_year_id = $sy->id;
        $classDetail->adviser_id = $adviser->id;
        $classDetail->grade_level = 8;
        $classDetail->term_type = 'new';
        $classDetail->status = 1;
        $classDetail->save();

        $enrollment = new Enrollment();
        $enrollment->student_information_id = $student->id;
        $enrollment->class_details_id = $classDetail->id;
        $enrollment->status = 1;
        $enrollment->save();

        $subject = new SubjectDetail();
        $subject->subject_code = 'ENG8';
        $subject->subject = 'English 8';
        $subject->save();

        $classSubject = new ClassSubjectDetail();
        $classSubject->class_details_id = $classDetail->id;
        $classSubject->subject_id = $subject->id;
        $classSubject->faculty_id = $adviser->id;
        $classSubject->class_subject_order = 1;
        $classSubject->status = 1;
        $classSubject->save();

        $enrolledSubject = new StudentEnrolledSubject();
        $enrolledSubject->enrollments_id = $enrollment->id;
        $enrolledSubject->class_subject_details_id = $classSubject->id;
        $enrolledSubject->subject_id = $subject->id;
        $enrolledSubject->fir_g = 85;
        $enrolledSubject->sec_g = 90;
        $enrolledSubject->thi_g = 95;
        $enrolledSubject->status = 1;
        $enrolledSubject->save();

        // Create Release Schedules: Term 1 is published, Term 2 is draft, Term 3 is draft
        GradeReleaseSchedule::create([
            'school_year_id' => $sy->id,
            'term_type'      => 'new',
            'term'           => 1,
            'is_enabled'     => 1,
            'status'         => 'published',
            'published_at'   => now(),
        ]);

        GradeReleaseSchedule::create([
            'school_year_id' => $sy->id,
            'term_type'      => 'new',
            'term'           => 2,
            'is_enabled'     => 0,
            'status'         => 'draft',
            'published_at'   => null,
        ]);

        GradeReleaseSchedule::create([
            'school_year_id' => $sy->id,
            'term_type'      => 'new',
            'term'           => 3,
            'is_enabled'     => 0,
            'status'         => 'draft',
            'published_at'   => null,
        ]);

        $response = $this->actingAs($user)->getJson('/api/grade-sheets');

        $response->assertStatus(200);
        $results = $response->json('results');

        // Check release schedules array
        $this->assertArrayHasKey('grade_release_schedules', $results);
        $this->assertCount(3, $results['grade_release_schedules']);

        $schedules = $results['grade_release_schedules'];
        $this->assertEquals(1, $schedules[0]['term']);
        $this->assertEquals(1, $schedules[0]['is_enabled']);
        $this->assertEquals('published', $schedules[0]['status']);
        $this->assertTrue($schedules[0]['is_revealed']);
        $this->assertFalse($schedules[0]['is_locked']);

        $this->assertEquals(2, $schedules[1]['term']);
        $this->assertEquals(0, $schedules[1]['is_enabled']);
        $this->assertEquals('draft', $schedules[1]['status']);
        $this->assertFalse($schedules[1]['is_revealed']);
        $this->assertTrue($schedules[1]['is_locked']);

        $this->assertEquals(3, $schedules[2]['term']);
        $this->assertEquals(0, $schedules[2]['is_enabled']);
        $this->assertEquals('draft', $schedules[2]['status']);
        $this->assertFalse($schedules[2]['is_revealed']);
        $this->assertTrue($schedules[2]['is_locked']);

        // Check grades: Term 1 grade is revealed (85), Term 2 & 3 are locked (0), final grade is blank
        $firstGrade = $results['grades'][0];
        $this->assertEquals(85, $firstGrade['fir_g']);
        $this->assertEquals(0, $firstGrade['sec_g']);
        $this->assertEquals(0, $firstGrade['thi_g']);
        $this->assertEquals('', $firstGrade['final_g']);
    }

    public function test_grade_sheets_formats_acronyms_and_roman_numerals_in_subject_and_sub_subject(): void
    {
        $user = User::factory()->create();

        $student = new StudentInformation();
        $student->user_id = $user->id;
        $student->first_name = 'Maria';
        $student->last_name = 'Clara';
        $student->save();

        $sy = new SchoolYear();
        $sy->school_year = '2025-2026';
        $sy->current = 1;
        $sy->status = 1;
        $sy->save();

        $section = new SectionDetail();
        $section->section = 'Grade 10 - Rizal';
        $section->grade_level = 10;
        $section->save();

        $adviser = new FacultyInformation();
        $adviser->first_name = 'Adviser';
        $adviser->last_name = 'One';
        $adviser->save();

        $classDetail = new ClassDetail();
        $classDetail->section_id = $section->id;
        $classDetail->school_year_id = $sy->id;
        $classDetail->adviser_id = $adviser->id;
        $classDetail->grade_level = 10;
        $classDetail->term_type = 'new';
        $classDetail->status = 1;
        $classDetail->save();

        $enrollment = new Enrollment();
        $enrollment->student_information_id = $student->id;
        $enrollment->class_details_id = $classDetail->id;
        $enrollment->status = 1;
        $enrollment->save();

        $subjectsData = [
            ['code' => 'TLE10', 'title' => 'TECHNOLOGY AND LIVELIHOOD EDUCATION (TLE)', 'order' => 1],
            ['code' => 'ESP10', 'title' => 'EDUKASYON SA PAGPAPAKATAO (ESP)', 'order' => 2],
            ['code' => 'AP10', 'title' => 'ARALING PANLIPUNAN', 'order' => 3],
            ['code' => 'MAPEH10', 'title' => 'MAPEH', 'order' => 4],
        ];

        foreach ($subjectsData as $sData) {
            $subj = new SubjectDetail();
            $subj->subject_code = $sData['code'];
            $subj->subject = $sData['title'];
            $subj->save();

            $cs = new ClassSubjectDetail();
            $cs->class_details_id = $classDetail->id;
            $cs->subject_id = $subj->id;
            $cs->faculty_id = $adviser->id;
            $cs->class_subject_order = $sData['order'];
            $cs->status = 1;
            $cs->save();

            $enrolled = new StudentEnrolledSubject();
            $enrolled->enrollments_id = $enrollment->id;
            $enrolled->class_subject_details_id = $cs->id;
            $enrolled->subject_id = $subj->id;
            $enrolled->fir_g = 90;
            $enrolled->sec_g = 90;
            $enrolled->thi_g = 90;
            $enrolled->status = 1;
            $enrolled->save();
        }

        $response = $this->actingAs($user)->getJson('/api/grade-sheets');
        $response->assertStatus(200);

        $grades = $response->json('results.grades');
        $this->assertEquals('Technology and Livelihood Education (TLE)', $grades[0]['subject']);
        $this->assertEquals('Edukasyon sa Pagpapakatao (ESP)', $grades[1]['subject']);
        $this->assertEquals('Araling Panlipunan', $grades[2]['subject']);
        $this->assertEquals('MAPEH', $grades[3]['subject']);
    }

    public function test_senior_grade_sheets_formats_acronyms_and_roman_numerals_in_old_term(): void
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

        $section = new SectionDetail();
        $section->section = 'Grade 12 - STEM';
        $section->grade_level = 12;
        $section->save();

        $adviser = new FacultyInformation();
        $adviser->first_name = 'Teacher';
        $adviser->last_name = 'SHS';
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
        $enrollment->status = 1;
        $enrollment->save();

        $subjectsData = [
            ['code' => 'RES1', 'title' => 'PRACTICAL RESEARCH I', 'order' => 1, 'sem' => 1],
            ['code' => 'DRRR', 'title' => 'DISASTER READINESS AND RISK REDUCTION (DRRR)', 'order' => 2, 'sem' => 1],
            ['code' => 'SMAW', 'title' => 'TVL - SMAW (NC II)', 'order' => 3, 'sem' => 1],
        ];

        foreach ($subjectsData as $sData) {
            $subj = new SubjectDetail();
            $subj->subject_code = $sData['code'];
            $subj->subject = $sData['title'];
            $subj->save();

            $cs = new ClassSubjectDetail();
            $cs->class_details_id = $classDetail->id;
            $cs->subject_id = $subj->id;
            $cs->faculty_id = $adviser->id;
            $cs->class_subject_order = $sData['order'];
            $cs->sem = $sData['sem'];
            $cs->status = 1;
            $cs->save();

            $enrolled = new StudentEnrolledSubject();
            $enrolled->enrollments_id = $enrollment->id;
            $enrolled->class_subject_details_id = $cs->id;
            $enrolled->subject_id = $subj->id;
            $enrolled->fir_g = 95;
            $enrolled->sec_g = 95;
            $enrolled->sem = $sData['sem'];
            $enrolled->status = 1;
            $enrolled->save();
        }

        $response = $this->actingAs($user)->getJson('/api/grade-sheets');
        $response->assertStatus(200);

        $grades = $response->json('results.first_sem.grades');
        $this->assertEquals('Practical Research I', $grades[0]['subject']);
        $this->assertEquals('Disaster Readiness and Risk Reduction (DRRR)', $grades[1]['subject']);
        $this->assertEquals('TVL - SMAW (NC II)', $grades[2]['subject']);
    }
}

