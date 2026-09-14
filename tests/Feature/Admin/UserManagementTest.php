<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsRole(string $role): User
    {
        $user = User::factory()->create(['role' => $role]);

        $this->actingAs($user, 'sanctum');

        return $user;
    }

    public function test_guest_cannot_access_admin_users(): void
    {
        $this->getJson('/api/admin/users')->assertStatus(401);
    }

    public function test_regular_user_cannot_access_admin_users(): void
    {
        $this->actingAsRole(User::ROLE_USER);

        $this->getJson('/api/admin/users')->assertStatus(403);
    }

    public function test_admin_can_list_users(): void
    {
        $this->actingAsRole(User::ROLE_ADMIN);

        $this->getJson('/api/admin/users')
            ->assertStatus(200)
            ->assertJsonStructure(['data' => [['id', 'name', 'email', 'role', 'is_active', 'created_at']]]);
    }

    public function test_admin_can_promote_user_to_admin(): void
    {
        $this->actingAsRole(User::ROLE_ADMIN);
        $target = User::factory()->create(['role' => User::ROLE_USER]);

        $this->patchJson("/api/admin/users/{$target->id}", ['role' => User::ROLE_ADMIN])
            ->assertStatus(200)
            ->assertJsonPath('data.role', User::ROLE_ADMIN);

        $this->assertDatabaseHas('users', ['id' => $target->id, 'role' => User::ROLE_ADMIN]);
    }

    public function test_admin_can_demote_admin_to_user(): void
    {
        $this->actingAsRole(User::ROLE_ADMIN);
        $target = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->patchJson("/api/admin/users/{$target->id}", ['role' => User::ROLE_USER])
            ->assertStatus(200)
            ->assertJsonPath('data.role', User::ROLE_USER);

        $this->assertDatabaseHas('users', ['id' => $target->id, 'role' => User::ROLE_USER]);
    }

    public function test_admin_can_deactivate_and_activate_user(): void
    {
        $this->actingAsRole(User::ROLE_ADMIN);
        $target = User::factory()->create();

        $this->patchJson("/api/admin/users/{$target->id}", ['is_active' => false])
            ->assertStatus(200)
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('users', ['id' => $target->id, 'is_active' => false]);

        $this->patchJson("/api/admin/users/{$target->id}", ['is_active' => true])
            ->assertStatus(200)
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('users', ['id' => $target->id, 'is_active' => true]);
    }

    public function test_validation_fails_on_invalid_role(): void
    {
        $this->actingAsRole(User::ROLE_ADMIN);
        $target = User::factory()->create();

        $this->patchJson("/api/admin/users/{$target->id}", ['role' => 'superhuman'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['role']);
    }

    public function test_admin_cannot_change_own_role(): void
    {
        $admin = $this->actingAsRole(User::ROLE_ADMIN);

        $this->patchJson("/api/admin/users/{$admin->id}", ['role' => User::ROLE_USER])
            ->assertStatus(403);

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'role' => User::ROLE_ADMIN]);
    }

    public function test_admin_cannot_deactivate_own_account(): void
    {
        $admin = $this->actingAsRole(User::ROLE_ADMIN);

        $this->patchJson("/api/admin/users/{$admin->id}", ['is_active' => false])
            ->assertStatus(403);

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'is_active' => true]);
    }

    public function test_admin_cannot_modify_super_admin(): void
    {
        $this->actingAsRole(User::ROLE_ADMIN);
        $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $this->patchJson("/api/admin/users/{$superAdmin->id}", ['role' => User::ROLE_USER])
            ->assertStatus(403);

        $this->assertDatabaseHas('users', ['id' => $superAdmin->id, 'role' => User::ROLE_SUPER_ADMIN]);
    }

    public function test_super_admin_can_modify_any_user(): void
    {
        $this->actingAsRole(User::ROLE_SUPER_ADMIN);
        $target = User::factory()->create(['role' => User::ROLE_USER]);

        $this->patchJson("/api/admin/users/{$target->id}", ['role' => User::ROLE_ADMIN, 'is_active' => false])
            ->assertStatus(200);

        $this->assertDatabaseHas('users', ['id' => $target->id, 'role' => User::ROLE_ADMIN, 'is_active' => false]);
    }

    public function test_deactivated_user_cannot_login(): void
    {
        $user = User::factory()->create(['email' => 'disabled@example.com', 'is_active' => false]);

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
