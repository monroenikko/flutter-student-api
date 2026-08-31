<?php

namespace Tests\Feature;

use App\Models\SchoolCalendarEvent;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SchoolCalendarApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestTables();
    }

    private function createTestTables(): void
    {
        if (! Schema::hasTable('school_calendar_events')) {
            Schema::create('school_calendar_events', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('title');
                $table->text('description')->nullable();
                $table->date('event_date');
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->string('type', 20)->default('note');
                $table->boolean('is_broadcast')->default(0);
                $table->string('audience', 30)->default('all');
                $table->boolean('created_by_admin')->default(0);
                $table->timestamps();
            });
        }
    }

    public function test_unauthenticated_user_cannot_access_school_calendar(): void
    {
        $response = $this->getJson('/api/school-calendar');
        $response->assertStatus(401);
    }

    public function test_student_can_fetch_calendar_events_including_auto_generated_holidays(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/school-calendar?year=2026');

        $response->assertStatus(200)
            ->assertJson([
                'code'    => 200,
                'message' => 'School calendar events successfully fetched.',
            ])
            ->assertJsonStructure([
                'results' => [
                    'events' => [
                        '*' => [
                            'id',
                            'title',
                            'description',
                            'event_date',
                            'formatted_date',
                            'day_name',
                            'day_number',
                            'month_name',
                            'year',
                            'formatted_time',
                            'type',
                            'type_label',
                            'type_color',
                            'is_broadcast',
                            'audience',
                            'can_edit',
                        ],
                    ],
                    'counts' => [
                        'all',
                        'holiday',
                        'event',
                        'reminder',
                        'note',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('school_calendar_events', [
            'title' => "New Year's Day",
            'type'  => 'holiday',
        ]);

        $results = $response->json('results');
        $this->assertGreaterThan(0, $results['counts']['holiday']);
    }

    public function test_student_receives_broadcasted_events_targeted_to_students_or_all(): void
    {
        $user = User::factory()->create();

        // 1. Broadcasted to 'all'
        SchoolCalendarEvent::create([
            'user_id'          => 999,
            'title'            => 'General Assembly',
            'description'      => 'All school campus assembly.',
            'event_date'       => '2026-09-01',
            'type'             => 'event',
            'is_broadcast'     => 1,
            'audience'         => 'all',
            'created_by_admin' => 1,
        ]);

        // 2. Broadcasted to 'students'
        SchoolCalendarEvent::create([
            'user_id'          => 999,
            'title'            => 'Student Council Elections',
            'description'      => 'Voting is open today.',
            'event_date'       => '2026-09-05',
            'type'             => 'event',
            'is_broadcast'     => 1,
            'audience'         => 'students',
            'created_by_admin' => 1,
        ]);

        // 3. Broadcasted to 'faculty' only (should NOT be visible to student)
        SchoolCalendarEvent::create([
            'user_id'          => 999,
            'title'            => 'Faculty Meeting Only',
            'description'      => 'Quarterly faculty check-in.',
            'event_date'       => '2026-09-10',
            'type'             => 'event',
            'is_broadcast'     => 1,
            'audience'         => 'faculty',
            'created_by_admin' => 1,
        ]);

        $response = $this->actingAs($user)->getJson('/api/school-calendar?start_date=2026-09-01&end_date=2026-09-30');

        $response->assertStatus(200);
        $titles = collect($response->json('results.events'))->pluck('title')->toArray();

        $this->assertContains('General Assembly', $titles);
        $this->assertContains('Student Council Elections', $titles);
        $this->assertNotContains('Faculty Meeting Only', $titles);
    }

    public function test_student_can_view_single_calendar_event(): void
    {
        $user = User::factory()->create();

        $event = SchoolCalendarEvent::create([
            'user_id'          => 999,
            'title'            => 'Science Fair 2026',
            'description'      => 'Annual high school science fair.',
            'event_date'       => '2026-10-15',
            'start_time'       => '09:00:00',
            'end_time'         => '15:00:00',
            'type'             => 'event',
            'is_broadcast'     => 1,
            'audience'         => 'students',
            'created_by_admin' => 1,
        ]);

        $response = $this->actingAs($user)->getJson("/api/school-calendar/{$event->id}");

        $response->assertStatus(200)
            ->assertJsonPath('results.id', $event->id)
            ->assertJsonPath('results.title', 'Science Fair 2026')
            ->assertJsonPath('results.formatted_time', '9:00 AM - 3:00 PM')
            ->assertJsonPath('results.type', 'event');
    }

    public function test_student_cannot_view_non_student_private_event(): void
    {
        $user = User::factory()->create();

        $privateFacultyEvent = SchoolCalendarEvent::create([
            'user_id'          => 999,
            'title'            => 'Admin Private Planning',
            'description'      => 'Internal staff only.',
            'event_date'       => '2026-10-20',
            'type'             => 'note',
            'is_broadcast'     => 0,
            'audience'         => 'faculty',
            'created_by_admin' => 1,
        ]);

        $response = $this->actingAs($user)->getJson("/api/school-calendar/{$privateFacultyEvent->id}");
        $response->assertStatus(404);
    }
}
