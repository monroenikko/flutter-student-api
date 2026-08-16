<?php

namespace Tests\Feature\Notification;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

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
}
