<?php

namespace Tests\Feature\Admin;

use App\Models\Tool;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ToolManagementTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsRole(string $role): User
    {
        $user = User::factory()->create(['role' => $role]);

        $this->actingAs($user, 'sanctum');

        return $user;
    }

    public function test_regular_user_cannot_access_admin_tools(): void
    {
        $this->actingAsRole(User::ROLE_USER);

        $this->getJson('/api/admin/tools')->assertStatus(403);
    }

    public function test_guest_cannot_access_admin_tools(): void
    {
        $this->getJson('/api/admin/tools')->assertStatus(401);
    }

    public function test_admin_can_list_tools(): void
    {
        $this->actingAsRole(User::ROLE_ADMIN);
        Tool::factory()->count(2)->create();

        $this->getJson('/api/admin/tools')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => [['id', 'name', 'slug', 'type']]]);
    }

    public function test_admin_can_create_tool(): void
    {
        $this->actingAsRole(User::ROLE_ADMIN);

        $response = $this->postJson('/api/admin/tools', [
            'name' => 'TimeSync',
            'slug' => 'timesync',
            'description' => 'Syncs time across your devices.',
            'type' => Tool::TYPE_EXTENSION,
            'pricing_model' => Tool::PRICING_ONE_TIME,
            'price' => 19.99,
            'device_limit' => 1,
            'referral_credits' => 5,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.slug', 'timesync')
            ->assertJsonPath('data.is_active', true);
        $this->assertDatabaseHas('tools', ['slug' => 'timesync']);
    }

    public function test_validation_fails_on_missing_fields(): void
    {
        $this->actingAsRole(User::ROLE_ADMIN);

        $this->postJson('/api/admin/tools', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'slug', 'description', 'type', 'pricing_model', 'price', 'device_limit']);
    }

    public function test_slug_must_be_unique_on_create(): void
    {
        $this->actingAsRole(User::ROLE_ADMIN);
        Tool::factory()->create(['slug' => 'timesync']);

        $this->postJson('/api/admin/tools', [
            'name' => 'TimeSync Duplicate',
            'slug' => 'timesync',
            'description' => 'Duplicate.',
            'type' => Tool::TYPE_EXTENSION,
            'pricing_model' => Tool::PRICING_ONE_TIME,
            'price' => 5,
            'device_limit' => 1,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    }

    public function test_admin_can_view_tool_with_files(): void
    {
        $this->actingAsRole(User::ROLE_ADMIN);
        $tool = Tool::factory()->create(['slug' => 'timesync']);
        $tool->files()->create([
            'file_path' => 'tools/1/timesync-v1.0.0.zip',
            'file_type' => 'zip',
            'version' => '1.0.0',
        ]);

        $this->getJson("/api/admin/tools/{$tool->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.name', $tool->name)
            ->assertJsonCount(1, 'data.files')
            ->assertJsonPath('data.files.0.file_type', 'zip');
    }

    public function test_admin_can_update_tool(): void
    {
        $this->actingAsRole(User::ROLE_ADMIN);
        $tool = Tool::factory()->create(['slug' => 'timesync']);

        $this->putJson("/api/admin/tools/{$tool->id}", [
            'name' => 'TimeSync Pro',
            'slug' => 'timesync',
            'description' => 'Updated description.',
            'type' => Tool::TYPE_EXTENSION,
            'pricing_model' => Tool::PRICING_ONE_TIME,
            'price' => 29.99,
            'device_limit' => 2,
        ])->assertStatus(200)
            ->assertJsonPath('data.name', 'TimeSync Pro');

        $this->assertDatabaseHas('tools', ['id' => $tool->id, 'name' => 'TimeSync Pro']);
    }

    public function test_slug_can_remain_unchanged_on_update(): void
    {
        $this->actingAsRole(User::ROLE_ADMIN);
        $tool = Tool::factory()->create(['slug' => 'timesync']);

        $this->putJson("/api/admin/tools/{$tool->id}", [
            'name' => 'TimeSync',
            'slug' => 'timesync',
            'description' => 'Unchanged slug.',
            'type' => Tool::TYPE_EXTENSION,
            'pricing_model' => Tool::PRICING_ONE_TIME,
            'price' => 19.99,
            'device_limit' => 1,
        ])->assertStatus(200);
    }

    public function test_super_admin_can_delete_tool(): void
    {
        $this->actingAsRole(User::ROLE_SUPER_ADMIN);
        $tool = Tool::factory()->create();

        $this->deleteJson("/api/admin/tools/{$tool->id}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('tools', ['id' => $tool->id]);
    }
}
