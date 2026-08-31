<?php

namespace Tests\Feature\Notification;

use App\Models\SiblingGroup;
use App\Models\SiblingGroupMember;
use App\Models\StudentInformation;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestTables();
    }

    private function createTestTables(): void
    {
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
                $table->unsignedInteger('sibling_group_id');
                $table->unsignedInteger('student_information_id');
                $table->timestamps();
            });
        }
    }

    public function test_notifications_index_requires_auth()
    {
        $response = $this->getJson('/api/notifications');
        $response->assertStatus(401);
    }

    public function test_notifications_index_returns_user_notifications()
    {
        $user = User::factory()->create();

        $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\Notifications\TestNotification',
            'data' => json_encode(['title' => 'Test Notification']),
            'read_at' => null,
        ]);

        $response = $this->actingAs($user)->getJson('/api/notifications');

        $response->assertStatus(200)
            ->assertJson([
                'code' => 200,
                'message' => 'Notifications retrieved successfully.',
            ])
            ->assertJsonPath('results.unread_count', 1);
    }

    public function test_mark_notification_as_read()
    {
        $user = User::factory()->create();
        $notificationId = (string) Str::uuid();

        $user->notifications()->create([
            'id' => $notificationId,
            'type' => 'App\Notifications\TestNotification',
            'data' => json_encode(['title' => 'Test Notification']),
            'read_at' => null,
        ]);

        $response = $this->actingAs($user)->postJson("/api/notifications/{$notificationId}/read");

        $response->assertStatus(200)
            ->assertJson([
                'code' => 200,
                'message' => 'Notification marked as read.',
            ]);

        $this->assertNotNull($user->notifications()->find($notificationId)->read_at);
    }

    public function test_mark_notification_as_unread()
    {
        $user = User::factory()->create();
        $notificationId = (string) Str::uuid();

        $user->notifications()->create([
            'id' => $notificationId,
            'type' => 'App\Notifications\TestNotification',
            'data' => json_encode(['title' => 'Test Notification']),
            'read_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson("/api/notifications/{$notificationId}/unread");

        $response->assertStatus(200)
            ->assertJson([
                'code' => 200,
                'message' => 'Notification marked as unread.',
            ]);

        $this->assertNull($user->notifications()->find($notificationId)->read_at);
    }

    public function test_mark_all_notifications_as_read()
    {
        $user = User::factory()->create();

        $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\Notifications\TestNotification',
            'data' => json_encode(['title' => 'Test 1']),
            'read_at' => null,
        ]);

        $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\Notifications\TestNotification',
            'data' => json_encode(['title' => 'Test 2']),
            'read_at' => null,
        ]);

        $response = $this->actingAs($user)->postJson('/api/notifications/read-all');

        $response->assertStatus(200)
            ->assertJson([
                'code' => 200,
                'message' => 'All notifications marked as read.',
            ]);

        $this->assertEquals(0, $user->unreadNotifications()->count());
    }

    public function test_mark_all_notifications_as_unread()
    {
        $user = User::factory()->create();

        $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\Notifications\TestNotification',
            'data' => json_encode(['title' => 'Test 1']),
            'read_at' => now(),
        ]);

        $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\Notifications\TestNotification',
            'data' => json_encode(['title' => 'Test 2']),
            'read_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson('/api/notifications/unread-all');

        $response->assertStatus(200)
            ->assertJson([
                'code' => 200,
                'message' => 'All notifications marked as unread.',
            ]);

        $this->assertEquals(2, $user->unreadNotifications()->count());
    }

    public function test_notifications_index_returns_specific_student_notifications_when_student_id_passed(): void
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

        // Student 2 has a payment approval notification from Finance
        $user2->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\Notifications\RegistrationNotification',
            'data' => json_encode([
                'title' => 'Payment Approved',
                'message' => 'Your tuition payment has been approved by Finance.',
                'student_id' => $student2->id,
            ]),
            'read_at' => null,
        ]);

        // Logged in as User 1, request Student 2's notifications
        $response = $this->actingAs($user1)->getJson('/api/notifications?student_id=' . $student2->id);

        $response->assertStatus(200)
            ->assertJsonPath('results.unread_count', 1)
            ->assertJsonPath('results.notifications.data.0.type', 'App\Notifications\RegistrationNotification')
            ->assertJsonPath('results.notifications.data.0.title', 'Payment Approved');
    }

    public function test_notifications_index_includes_grade_release_notifications(): void
    {
        $user = User::factory()->create();
        $notificationId = (string) Str::uuid();

        $user->notifications()->create([
            'id' => $notificationId,
            'type' => 'App\Notifications\TermGradeReleasedNotification',
            'data' => json_encode([
                'title'          => '1st Term Grades Available',
                'message'        => 'Your grades for 1st Term (S.Y. 2026-2027) have been released. You can now view your grade sheet.',
                'term'           => 1,
                'term_type'      => 'new',
                'school_year_id' => 16,
                'type'           => 'grade_release',
                'created_at'     => now()->toDateTimeString(),
            ]),
            'read_at' => null,
        ]);

        $response = $this->actingAs($user)->getJson('/api/notifications');

        $response->assertStatus(200)
            ->assertJson([
                'code' => 200,
                'message' => 'Notifications retrieved successfully.',
            ])
            ->assertJsonPath('results.unread_count', 1)
            ->assertJsonPath('results.notifications.data.0.id', $notificationId)
            ->assertJsonPath('results.notifications.data.0.title', '1st Term Grades Available')
            ->assertJsonPath('results.notifications.data.0.notification_type', 'grade_release')
            ->assertJsonPath('results.notifications.data.0.term', 1)
            ->assertJsonPath('results.notifications.data.0.term_type', 'new')
            ->assertJsonPath('results.notifications.data.0.school_year_id', 16)
            ->assertJsonPath('results.notifications.data.0.is_read', false);

        // Mark as read
        $markReadResponse = $this->actingAs($user)->postJson("/api/notifications/{$notificationId}/read");
        $markReadResponse->assertStatus(200);

        $checkResponse = $this->actingAs($user)->getJson('/api/notifications');
        $checkResponse->assertStatus(200)
            ->assertJsonPath('results.unread_count', 0)
            ->assertJsonPath('results.notifications.data.0.is_read', true);
    }
}
