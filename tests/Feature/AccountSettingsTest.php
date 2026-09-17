<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AccountSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_name_and_email_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/me', [
                'name' => 'New Name',
                'email' => 'new@example.com',
            ])
            ->assertStatus(200)
            ->assertJsonPath('name', 'New Name')
            ->assertJsonPath('email', 'new@example.com');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);
    }

    public function test_profile_update_rejects_email_belonging_to_another_user(): void
    {
        $user = User::factory()->create(['email' => 'mine@example.com']);
        User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/me', ['email' => 'taken@example.com'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_profile_update_keeps_own_email(): void
    {
        $user = User::factory()->create(['email' => 'mine@example.com']);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/me', ['email' => 'mine@example.com'])
            ->assertStatus(200);
    }

    public function test_profile_update_requires_authentication(): void
    {
        $this->patchJson('/api/me', ['name' => 'Nope'])->assertStatus(401);
    }

    public function test_user_can_upload_avatar(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->post('/api/me/avatar', [
                'avatar' => UploadedFile::fake()->image('avatar.png', 100, 100),
            ])
            ->assertStatus(200);

        $response->assertJsonPath('id', $user->id);
        $this->assertNotNull($user->fresh()->avatar_path);
        $this->assertStringContainsString('/api/me/avatar', $response->json('avatar_url'));

        Storage::disk('local')->assertExists($user->fresh()->avatar_path);
    }

    public function test_avatar_upload_rejects_non_image(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->post('/api/me/avatar', [
                'avatar' => UploadedFile::fake()->create('document.txt', 10),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('avatar');
    }

    public function test_uploading_new_avatar_removes_previous_file(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $first = UploadedFile::fake()->image('first.png', 100, 100);

        $this->actingAs($user, 'sanctum')->post('/api/me/avatar', ['avatar' => $first]);

        $oldPath = $user->fresh()->avatar_path;
        Storage::disk('local')->assertExists($oldPath);

        $this->actingAs($user, 'sanctum')
            ->post('/api/me/avatar', [
                'avatar' => UploadedFile::fake()->image('second.jpg', 100, 100),
            ])
            ->assertStatus(200)
            ->assertJsonPath('avatar_path', $user->fresh()->avatar_path);

        Storage::disk('local')->assertMissing($oldPath);
    }

    public function test_avatar_can_be_streamed_back(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->post('/api/me/avatar', [
                'avatar' => UploadedFile::fake()->image('avatar.png', 100, 100),
            ]);

        $this->actingAs($user, 'sanctum')
            ->get('/api/me/avatar')
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'image/png');
    }

    public function test_avatar_stream_returns_404_when_no_avatar(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->get('/api/me/avatar')
            ->assertStatus(404);
    }

    public function test_user_can_remove_avatar(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->post('/api/me/avatar', [
            'avatar' => UploadedFile::fake()->image('avatar.png', 100, 100),
        ]);
        $path = $user->fresh()->avatar_path;

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/me/avatar')
            ->assertStatus(200)
            ->assertJsonPath('avatar_path', null)
            ->assertJsonPath('avatar_url', null);

        $this->assertNull($user->fresh()->avatar_path);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_password_change_requires_current_password(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/me/password', [
                'password' => 'new-secret-password',
                'password_confirmation' => 'new-secret-password',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('current_password');
    }

    public function test_password_change_with_wrong_current_password_fails(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/me/password', [
                'current_password' => 'not-the-password',
                'password' => 'new-secret-password',
                'password_confirmation' => 'new-secret-password',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('current_password');
    }

    public function test_password_change_revokes_other_sessions(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);
        $currentToken = $user->createToken('auth-token')->plainTextToken;
        $otherToken = $user->createToken('other-session')->plainTextToken;

        $this->assertNotNull(PersonalAccessToken::findToken($otherToken));

        $this->withHeader('Authorization', "Bearer $currentToken")
            ->putJson('/api/me/password', [
                'current_password' => 'old-password',
                'password' => 'new-secret-password',
                'password_confirmation' => 'new-secret-password',
            ])
            ->assertStatus(200);

        $this->assertTrue(Hash::check('new-secret-password', $user->fresh()->password));
        $this->assertNull(PersonalAccessToken::findToken($otherToken));
        $this->assertNotNull(PersonalAccessToken::findToken($currentToken));
    }

    public function test_forgot_password_sends_reset_link_and_creates_record(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'reset@example.com']);

        $this->postJson('/api/forgot-password', ['email' => 'reset@example.com'])
            ->assertStatus(200);

        Notification::assertSentTo($user, ResetPassword::class);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => 'reset@example.com']);
    }

    public function test_forgot_password_does_not_reveal_unknown_email(): void
    {
        $this->postJson('/api/forgot-password', ['email' => 'ghost@example.com'])
            ->assertStatus(200);
    }

    public function test_forgot_password_is_rate_limited(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 5) as $i) {
            $this->postJson('/api/forgot-password', ['email' => $user->email])->assertStatus(200);
        }

        $this->postJson('/api/forgot-password', ['email' => $user->email])->assertStatus(429);
    }

    public function test_user_can_reset_password_with_valid_token(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);
        $token = Password::broker()->createToken($user);

        $response = $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ])->assertStatus(200);

        $response->assertJsonStructure(['user', 'token']);
        $this->assertTrue(Hash::check('brand-new-password', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_reset_password_rejects_invalid_token(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'token' => Str::random(64),
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ])->assertStatus(422);
    }
}
