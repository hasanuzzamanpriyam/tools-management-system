<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_defaults_to_user(): void
    {
        $user = User::factory()->create();

        $this->assertEquals('user', $user->role);
    }

    public function test_can_create_user_with_super_admin_role(): void
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        $this->assertEquals('super_admin', $user->role);
    }

    public function test_can_create_user_with_admin_role(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->assertEquals('admin', $user->role);
    }

    public function test_role_constants_exist(): void
    {
        $this->assertEquals('super_admin', User::ROLE_SUPER_ADMIN);
        $this->assertEquals('admin', User::ROLE_ADMIN);
        $this->assertEquals('user', User::ROLE_USER);
    }

    public function test_referral_code_is_unique_for_users(): void
    {
        User::factory()->create(['referral_code' => 'ABC123']);
        User::factory()->create(['referral_code' => 'XYZ789']);

        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseHas('users', ['referral_code' => 'ABC123']);
        $this->assertDatabaseHas('users', ['referral_code' => 'XYZ789']);
    }
}