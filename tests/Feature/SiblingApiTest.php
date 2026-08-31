<?php

namespace Tests\Feature;

use App\Models\ClassDetail;
use App\Models\ClassSubjectDetail;
use App\Models\Enrollment;
use App\Models\FacultyInformation;
use App\Models\SchoolYear;
use App\Models\SectionDetail;
use App\Models\SiblingGroup;
use App\Models\SiblingGroupMember;
use App\Models\StudentEnrolledSubject;
use App\Models\StudentInformation;
use App\Models\SubjectCategory;
use App\Models\SubjectDetail;
use App\Models\SubSubjectDetail;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SiblingApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestTables();
    }

    private function createTestTables(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'username')) {
                    $table->string('username')->nullable();
                }
                if (!Schema::hasColumn('users', 'status')) {
                    $table->tinyInteger('status')->default(1);
                }
            });
        }

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
                $table->string('photo')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('sibling_groups')) {
            Schema::create('sibling_groups', function (Blueprint $table) {
                $table->increments('id');
                $table->string('group_code')->unique();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('sibling_group_members')) {
            Schema::create('sibling_group_members', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('sibling_group_id')->nullable();
                $table->unsignedInteger('student_information_id')->nullable();
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

        if (! Schema::hasTable('teacher_subjects')) {
            Schema::create('teacher_subjects', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('class_subject_details_id')->nullable();
                $table->unsignedInteger('faculty_id')->nullable();
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

        if (! Schema::hasTable('subscriptions')) {
            Schema::create('subscriptions', function (Blueprint $table) {
                $table->increments('id');
                $table->string('subscribable_type');
                $table->unsignedBigInteger('subscribable_id');
                $table->string('player_id');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('audits')) {
            Schema::create('audits', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('auditable_type')->nullable();
                $table->unsignedBigInteger('auditable_id')->nullable();
                $table->string('ip_address')->nullable();
                $table->text('user_agent')->nullable();
                $table->text('platform')->nullable();
                $table->text('url')->nullable();
                $table->string('method')->nullable();
                $table->text('data')->nullable();
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

    public function test_siblings_endpoint_returns_only_self_when_no_sibling_group_exists(): void
    {
        $user = User::factory()->create();

        $student = new StudentInformation();
        $student->user_id = $user->id;
        $student->first_name = 'John';
        $student->middle_name = 'M';
        $student->last_name = 'Doe';
        $student->save();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/siblings');

        $response->assertStatus(200)
            ->assertJsonPath('results.has_siblings', false)
            ->assertJsonPath('results.group_code', null)
            ->assertJsonCount(1, 'results.students')
            ->assertJsonPath('results.students.0.id', $student->id);
    }

    public function test_siblings_endpoint_returns_all_linked_siblings(): void
    {
        $user1 = User::factory()->create();
        $student1 = new StudentInformation();
        $student1->user_id = $user1->id;
        $student1->first_name = 'John';
        $student1->last_name = 'Doe';
        $student1->save();

        $user2 = User::factory()->create();
        $student2 = new StudentInformation();
        $student2->user_id = $user2->id;
        $student2->first_name = 'Jane';
        $student2->last_name = 'Doe';
        $student2->save();

        $group = SiblingGroup::create(['group_code' => 'SG-00001']);
        SiblingGroupMember::create(['sibling_group_id' => $group->id, 'student_information_id' => $student1->id]);
        SiblingGroupMember::create(['sibling_group_id' => $group->id, 'student_information_id' => $student2->id]);

        Sanctum::actingAs($user1);

        $response = $this->getJson('/api/siblings');

        $response->assertStatus(200)
            ->assertJsonPath('results.has_siblings', true)
            ->assertJsonPath('results.group_code', 'SG-00001')
            ->assertJsonCount(2, 'results.students');
    }

    public function test_can_access_sibling_grades_with_student_id_param(): void
    {
        $sy = new SchoolYear();
        $sy->school_year = '2026-2027';
        $sy->current = 1;
        $sy->status = 1;
        $sy->save();

        $section = new SectionDetail();
        $section->section = 'St. Luke';
        $section->grade_level = 10;
        $section->save();

        $classDetail = new ClassDetail();
        $classDetail->section_id = $section->id;
        $classDetail->school_year_id = $sy->id;
        $classDetail->grade_level = 10;
        $classDetail->term_type = 'new';
        $classDetail->status = 1;
        $classDetail->save();

        $user1 = User::factory()->create();
        $student1 = new StudentInformation();
        $student1->user_id = $user1->id;
        $student1->first_name = 'John';
        $student1->last_name = 'Doe';
        $student1->save();

        $user2 = User::factory()->create();
        $student2 = new StudentInformation();
        $student2->user_id = $user2->id;
        $student2->first_name = 'Jane';
        $student2->last_name = 'Doe';
        $student2->save();

        // Enroll Student 2
        $enrollment2 = new Enrollment();
        $enrollment2->student_information_id = $student2->id;
        $enrollment2->class_details_id = $classDetail->id;
        $enrollment2->status = 1;
        $enrollment2->save();

        $subject = new SubjectDetail();
        $subject->subject_code = 'MATH10';
        $subject->subject = 'Mathematics 10';
        $subject->save();

        $csd = new ClassSubjectDetail();
        $csd->subject_id = $subject->id;
        $csd->class_details_id = $classDetail->id;
        $csd->class_subject_order = 1;
        $csd->save();

        $ses = new StudentEnrolledSubject();
        $ses->subject_id = $subject->id;
        $ses->enrollments_id = $enrollment2->id;
        $ses->class_subject_details_id = $csd->id;
        $ses->fir_g = 95;
        $ses->sec_g = 94;
        $ses->thi_g = 96;
        $ses->status = 1;
        $ses->save();

        // Link as siblings
        $group = SiblingGroup::create(['group_code' => 'SG-00001']);
        SiblingGroupMember::create(['sibling_group_id' => $group->id, 'student_information_id' => $student1->id]);
        SiblingGroupMember::create(['sibling_group_id' => $group->id, 'student_information_id' => $student2->id]);

        // Login as Student 1, request Student 2's grades
        Sanctum::actingAs($user1);

        $response = $this->getJson('/api/grade-sheets?student_id=' . $student2->id);

        $response->assertStatus(200)
            ->assertJsonPath('results.section', 'St. Luke')
            ->assertJsonPath('results.grade_level', 10);
    }

    public function test_cannot_access_unrelated_student_grades(): void
    {
        $user1 = User::factory()->create();
        $student1 = new StudentInformation();
        $student1->user_id = $user1->id;
        $student1->first_name = 'John';
        $student1->last_name = 'Doe';
        $student1->save();

        $userUnrelated = User::factory()->create();
        $studentUnrelated = new StudentInformation();
        $studentUnrelated->user_id = $userUnrelated->id;
        $studentUnrelated->first_name = 'Bob';
        $studentUnrelated->last_name = 'Smith';
        $studentUnrelated->save();

        Sanctum::actingAs($user1);

        $response = $this->getJson('/api/grade-sheets?student_id=' . $studentUnrelated->id);

        $response->assertStatus(403);
    }

    public function test_linked_siblings_model_method_returns_collection_excluding_self(): void
    {
        $student1 = new StudentInformation();
        $student1->first_name = 'Alice';
        $student1->last_name = 'Smith';
        $student1->save();

        $student2 = new StudentInformation();
        $student2->first_name = 'Bob';
        $student2->last_name = 'Smith';
        $student2->save();

        $student3 = new StudentInformation();
        $student3->first_name = 'Charlie';
        $student3->last_name = 'Smith';
        $student3->save();

        $group = SiblingGroup::create(['group_code' => SiblingGroup::generateGroupCode()]);
        SiblingGroupMember::create(['sibling_group_id' => $group->id, 'student_information_id' => $student1->id]);
        SiblingGroupMember::create(['sibling_group_id' => $group->id, 'student_information_id' => $student2->id]);
        SiblingGroupMember::create(['sibling_group_id' => $group->id, 'student_information_id' => $student3->id]);

        $this->assertEquals('SG-00001', $group->group_code);
        $this->assertCount(2, $student1->linkedSiblings());
        $this->assertTrue($student1->linkedSiblings()->pluck('id')->contains($student2->id));
        $this->assertTrue($student1->linkedSiblings()->pluck('id')->contains($student3->id));
        $this->assertFalse($student1->linkedSiblings()->pluck('id')->contains($student1->id));
    }

    public function test_user_endpoint_returns_switched_sibling_data_when_student_id_param_is_passed(): void
    {
        $user1 = User::factory()->create();
        $student1 = new StudentInformation();
        $student1->user_id = $user1->id;
        $student1->first_name = 'John';
        $student1->last_name = 'Doe';
        $student1->save();

        $user2 = User::factory()->create();
        $student2 = new StudentInformation();
        $student2->user_id = $user2->id;
        $student2->first_name = 'Jane';
        $student2->last_name = 'Doe';
        $student2->save();

        $group = SiblingGroup::create(['group_code' => 'SG-00001']);
        SiblingGroupMember::create(['sibling_group_id' => $group->id, 'student_information_id' => $student1->id]);
        SiblingGroupMember::create(['sibling_group_id' => $group->id, 'student_information_id' => $student2->id]);

        Sanctum::actingAs($user1);

        // Fetch own user data
        $response1 = $this->getJson('/api/user');
        $response1->assertStatus(200)
            ->assertJsonPath('results.user.first_name', 'John');

        // Fetch switched sibling user data
        $response2 = $this->getJson('/api/user?student_id=' . $student2->id);
        $response2->assertStatus(200)
            ->assertJsonPath('results.user.first_name', 'Jane');
    }

    public function test_login_syncs_player_id_subscription_across_all_siblings_in_sibling_group(): void
    {
        $user1 = User::factory()->create(['username' => 'student1', 'password' => bcrypt('Password123!')]);
        $student1 = new StudentInformation();
        $student1->user_id = $user1->id;
        $student1->first_name = 'Student';
        $student1->last_name = 'One';
        $student1->save();

        $user2 = User::factory()->create(['username' => 'student2']);
        $student2 = new StudentInformation();
        $student2->user_id = $user2->id;
        $student2->first_name = 'Student';
        $student2->last_name = 'Two';
        $student2->save();

        $user3 = User::factory()->create(['username' => 'student3']);
        $student3 = new StudentInformation();
        $student3->user_id = $user3->id;
        $student3->first_name = 'Student';
        $student3->last_name = 'Three';
        $student3->save();

        $group = SiblingGroup::create(['group_code' => 'SG-00001']);
        SiblingGroupMember::create(['sibling_group_id' => $group->id, 'student_information_id' => $student1->id]);
        SiblingGroupMember::create(['sibling_group_id' => $group->id, 'student_information_id' => $student2->id]);
        SiblingGroupMember::create(['sibling_group_id' => $group->id, 'student_information_id' => $student3->id]);

        $playerId = 'player-device-uuid-12345';

        // Login student1 with player_id
        $response = $this->postJson('/api/login', [
            'username' => 'student1',
            'password' => 'Password123!',
            'player_id' => $playerId,
        ]);

        $response->assertStatus(200);

        // Verify that subscriptions are created for all 3 sibling users with the exact same player_id
        $this->assertDatabaseHas('subscriptions', [
            'subscribable_type' => User::class,
            'subscribable_id' => $user1->id,
            'player_id' => $playerId,
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'subscribable_type' => User::class,
            'subscribable_id' => $user2->id,
            'player_id' => $playerId,
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'subscribable_type' => User::class,
            'subscribable_id' => $user3->id,
            'player_id' => $playerId,
        ]);

        $this->assertEquals(3, Subscription::where('player_id', $playerId)->count());
    }

    public function test_switching_student_or_fetching_siblings_syncs_existing_subscription_to_all_sibling_users(): void
    {
        $user1 = User::factory()->create();
        $student1 = new StudentInformation();
        $student1->user_id = $user1->id;
        $student1->first_name = 'Student';
        $student1->last_name = 'One';
        $student1->save();

        $user2 = User::factory()->create();
        $student2 = new StudentInformation();
        $student2->user_id = $user2->id;
        $student2->first_name = 'Student';
        $student2->last_name = 'Two';
        $student2->save();

        $group = SiblingGroup::create(['group_code' => 'SG-00001']);
        SiblingGroupMember::create(['sibling_group_id' => $group->id, 'student_information_id' => $student1->id]);
        SiblingGroupMember::create(['sibling_group_id' => $group->id, 'student_information_id' => $student2->id]);

        $playerId = 'device-sync-token-999';

        // Pre-existing subscription for student1
        Subscription::create([
            'subscribable_type' => User::class,
            'subscribable_id' => $user1->id,
            'player_id' => $playerId,
        ]);

        Sanctum::actingAs($user1);

        // Fetch user data with student switch
        $response = $this->getJson('/api/user?student_id=' . $student2->id);
        $response->assertStatus(200);

        // Verify student2 also got the subscription synced
        $this->assertDatabaseHas('subscriptions', [
            'subscribable_type' => User::class,
            'subscribable_id' => $user2->id,
            'player_id' => $playerId,
        ]);
    }
}
