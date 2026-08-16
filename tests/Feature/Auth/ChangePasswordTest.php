<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_change_password_requires_authentication()
    {
        $response = $this->postJson('/api/change-password', [
            'current_password' => 'OldP@ss123',
            'new_password' => 'NewP@ss123',
            'new_password_confirmation' => 'NewP@ss123',
        ]);

        $response->assertStatus(401);
    }

    public function test_change_password_requires_all_fields()
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldP@ss123'),
        ]);

        $response = $this->actingAs($user)->postJson('/api/change-password', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['current_password', 'new_password']);
    }

    public function test_change_password_fails_with_incorrect_current_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldP@ss123'),
        ]);

        $response = $this->actingAs($user)->postJson('/api/change-password', [
            'current_password' => 'wrongpassword',
            'new_password' => 'NewP@ss123',
            'new_password_confirmation' => 'NewP@ss123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);
    }

    public function test_change_password_fails_when_new_password_is_same_as_current()
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldP@ss123'),
        ]);

        $response = $this->actingAs($user)->postJson('/api/change-password', [
            'current_password' => 'OldP@ss123',
            'new_password' => 'OldP@ss123',
            'new_password_confirmation' => 'OldP@ss123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['new_password']);
    }

    public function test_change_password_fails_without_complex_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldP@ss123'),
        ]);

        // Simple password missing capital letter and symbol
        $response = $this->actingAs($user)->postJson('/api/change-password', [
            'current_password' => 'OldP@ss123',
            'new_password' => '123456',
            'new_password_confirmation' => '123456',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['new_password'])
            ->assertJsonFragment([
                'The new password must contain at least one uppercase and one lowercase letter.',
            ]);
    }

    public function test_change_password_fails_when_confirmation_does_not_match()
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldP@ss123'),
        ]);

        $response = $this->actingAs($user)->postJson('/api/change-password', [
            'current_password' => 'OldP@ss123',
            'new_password' => 'NewP@ss123',
            'new_password_confirmation' => 'DifferentP@ss123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['new_password']);
    }

    public function test_change_password_succeeds_with_valid_credentials()
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldP@ss123'),
        ]);

        $response = $this->actingAs($user)->postJson('/api/change-password', [
            'current_password' => 'OldP@ss123',
            'new_password' => 'NewP@ss123',
            'new_password_confirmation' => 'NewP@ss123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Password successfully changed.',
                'code' => 200,
            ]);

        $this->assertTrue(Hash::check('NewP@ss123', $user->fresh()->password));
    }
}
