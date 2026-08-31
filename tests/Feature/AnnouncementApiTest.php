<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\AnnouncementUserState;
use App\Models\ClassDetail;
use App\Models\Enrollment;
use App\Models\StudentInformation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class AnnouncementApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestTables();
    }

    private function createTestTables(): void
    {
        if (! Schema::hasTable('announcements')) {
            Schema::create('announcements', function (Blueprint $table) {
                $table->increments('id');
                $table->string('title');
                $table->text('content');
                $table->tinyInteger('audience')->default(3);
                $table->tinyInteger('status')->default(1);
                $table->unsignedInteger('user_id')->default(1);
                $table->timestamp('published_at')->nullable();
                $table->text('slug')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('announcement_user_states')) {
            Schema::create('announcement_user_states', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('announcement_id');
                $table->unsignedBigInteger('user_id');
                $table->unsignedTinyInteger('status')->default(2);
                $table->timestamps();

                $table->unique(['announcement_id', 'user_id']);
            });
        }

        if (! Schema::hasTable('student_informations')) {
            Schema::create('student_informations', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('user_id')->nullable();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('class_details')) {
            Schema::create('class_details', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('grade_level')->default(10);
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
                $table->tinyInteger('current')->default(1);
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });
        }
    }

    public function test_unauthenticated_user_cannot_access_announcements(): void
    {
        $response = $this->getJson('/api/announcements');
        $response->assertStatus(401);
    }

    public function test_student_can_list_announcements_for_their_level(): void
    {
        $user = User::factory()->create();

        $student = new StudentInformation();
        $student->user_id = $user->id;
        $student->first_name = 'Juan';
        $student->last_name = 'Dela Cruz';
        $student->save();

        $classDetail = new ClassDetail();
        $classDetail->grade_level = 10; // Junior High
        $classDetail->current = 1;
        $classDetail->status = 1;
        $classDetail->save();

        $enrollment = new Enrollment();
        $enrollment->student_information_id = $student->id;
        $enrollment->class_details_id = $classDetail->id;
        $enrollment->current = 1;
        $enrollment->status = 1;
        $enrollment->save();

        // 1 = Junior High, 2 = Senior High, 3 = All Students
        $announcement1 = Announcement::create([
            'title'        => 'Junior High Assembly',
            'content'      => 'Assembly for JHS students in the gymnasium.',
            'audience'     => 1,
            'status'       => 1,
            'user_id'      => 1,
            'published_at' => now(),
        ]);

        $announcement2 = Announcement::create([
            'title'        => 'General Campus Notice',
            'content'      => 'Holiday reminder for all students.',
            'audience'     => 3,
            'status'       => 1,
            'user_id'      => 1,
            'published_at' => now(),
        ]);

        $announcement3 = Announcement::create([
            'title'        => 'Senior High Career Fair',
            'content'      => 'Career orientation for SHS only.',
            'audience'     => 2,
            'status'       => 1,
            'user_id'      => 1,
            'published_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson('/api/announcements');

        $response->assertOk()
            ->assertJson([
                'code'    => 200,
                'message' => 'Announcements successfully fetched.',
            ])
            ->assertJsonPath('results.pagination.total', 2)
            ->assertJsonPath('results.counts.all', 2)
            ->assertJsonPath('results.counts.unread', 2);
    }

    public function test_student_can_view_single_announcement_and_marks_as_read(): void
    {
        $user = User::factory()->create();

        $announcement = Announcement::create([
            'title'        => 'New Library Books Available',
            'content'      => 'Check out the new fiction and research books at the library.',
            'audience'     => 3,
            'status'       => 1,
            'user_id'      => 1,
            'published_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson("/api/announcements/{$announcement->id}");

        $response->assertOk()
            ->assertJson([
                'code'    => 200,
                'message' => 'Announcement details fetched successfully.',
            ])
            ->assertJsonPath('results.id', $announcement->id)
            ->assertJsonPath('results.title', 'New Library Books Available')
            ->assertJsonPath('results.is_read', true)
            ->assertJsonPath('results.status', 2);

        $this->assertDatabaseHas('announcement_user_states', [
            'announcement_id' => $announcement->id,
            'user_id'         => $user->id,
            'status'          => 2,
        ]);
    }

    public function test_student_can_change_announcement_status_to_read_unread_and_archive(): void
    {
        $user = User::factory()->create();

        $announcement = Announcement::create([
            'title'        => 'Tuition Discount Policy',
            'content'      => 'Updated guidelines for early bird discounts.',
            'audience'     => 3,
            'status'       => 1,
            'user_id'      => 1,
            'published_at' => now(),
        ]);

        // 1. Mark as Read
        $readRes = $this->actingAs($user)->postJson("/api/announcements/{$announcement->id}/read");
        $readRes->assertOk()
            ->assertJson([
                'code'    => 200,
                'message' => 'Announcement marked as read.',
            ]);

        $this->assertDatabaseHas('announcement_user_states', [
            'announcement_id' => $announcement->id,
            'user_id'         => $user->id,
            'status'          => 2,
        ]);

        // 2. Mark as Archived
        $archiveRes = $this->actingAs($user)->postJson("/api/announcements/{$announcement->id}/archive");
        $archiveRes->assertOk()
            ->assertJson([
                'code'    => 200,
                'message' => 'Announcement moved to archived.',
            ]);

        $this->assertDatabaseHas('announcement_user_states', [
            'announcement_id' => $announcement->id,
            'user_id'         => $user->id,
            'status'          => 3,
        ]);

        // 3. Mark as Unread
        $unreadRes = $this->actingAs($user)->postJson("/api/announcements/{$announcement->id}/unread");
        $unreadRes->assertOk()
            ->assertJson([
                'code'    => 200,
                'message' => 'Announcement marked as unread.',
            ]);

        $this->assertDatabaseMissing('announcement_user_states', [
            'announcement_id' => $announcement->id,
            'user_id'         => $user->id,
        ]);
    }

    public function test_student_can_mark_all_announcements_as_read(): void
    {
        $user = User::factory()->create();

        $announcement1 = Announcement::create([
            'title'        => 'Announcement 1',
            'content'      => 'Content 1',
            'audience'     => 3,
            'status'       => 1,
            'user_id'      => 1,
            'published_at' => now(),
        ]);

        $announcement2 = Announcement::create([
            'title'        => 'Announcement 2',
            'content'      => 'Content 2',
            'audience'     => 3,
            'status'       => 1,
            'user_id'      => 1,
            'published_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson('/api/announcements/read-all');

        $response->assertOk()
            ->assertJson([
                'code'    => 200,
                'message' => 'All announcements marked as read.',
            ]);

        $this->assertDatabaseHas('announcement_user_states', [
            'announcement_id' => $announcement1->id,
            'user_id'         => $user->id,
            'status'          => 2,
        ]);

        $this->assertDatabaseHas('announcement_user_states', [
            'announcement_id' => $announcement2->id,
            'user_id'         => $user->id,
            'status'          => 2,
        ]);
    }

    public function test_student_can_fetch_announcements_targeted_to_all_users(): void
    {
        $user = User::factory()->create();

        $student = new StudentInformation();
        $student->user_id = $user->id;
        $student->first_name = 'Juan';
        $student->last_name = 'Dela Cruz';
        $student->save();

        $classDetail = new ClassDetail();
        $classDetail->grade_level = 11; // Senior High
        $classDetail->current = 1;
        $classDetail->status = 1;
        $classDetail->save();

        $enrollment = new Enrollment();
        $enrollment->student_information_id = $student->id;
        $enrollment->class_details_id = $classDetail->id;
        $enrollment->current = 1;
        $enrollment->status = 1;
        $enrollment->save();

        $announcement = Announcement::create([
            'title'        => 'System Maintenance Notice',
            'content'      => 'Server maintenance this weekend.',
            'audience'     => 4, // All Users
            'status'       => 1,
            'user_id'      => 1,
            'published_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson('/api/announcements');

        $response->assertOk()
            ->assertJsonPath('results.pagination.total', 1)
            ->assertJsonPath('results.data.0.title', 'System Maintenance Notice')
            ->assertJsonPath('results.data.0.audience', 4)
            ->assertJsonPath('results.data.0.audience_label', 'All Users');
    }
}
