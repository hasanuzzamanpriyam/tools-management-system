<?php

namespace Tests\Feature;

use App\Models\Tool;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_demo(): void
    {
        $tool = Tool::factory()->create(['has_demo' => true, 'demo_url' => 'https://demo.example.com']);

        $this->getJson("/api/tools/{$tool->id}/demo")->assertStatus(401);
    }

    public function test_demo_returns_url_when_tool_has_demo(): void
    {
        $user = User::factory()->create();
        $tool = Tool::factory()->create(['has_demo' => true, 'demo_url' => 'https://demo.example.com']);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/tools/{$tool->id}/demo")
            ->assertStatus(200)
            ->assertJson([
                'demo_url' => 'https://demo.example.com',
            ]);
    }

    public function test_demo_returns_not_found_when_tool_has_no_demo(): void
    {
        $user = User::factory()->create();
        $tool = Tool::factory()->create(['has_demo' => false, 'demo_url' => null]);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/tools/{$tool->id}/demo")
            ->assertStatus(404);
    }

    public function test_demo_returns_not_found_when_demo_url_missing(): void
    {
        $user = User::factory()->create();
        $tool = Tool::factory()->create(['has_demo' => true, 'demo_url' => null]);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/tools/{$tool->id}/demo")
            ->assertStatus(404);
    }

    public function test_admin_can_set_demo_fields_when_creating_tool(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/tools', [
                'name' => 'Demo Tool',
                'slug' => 'demo-tool',
                'description' => 'A tool with a demo.',
                'type' => Tool::TYPE_EXTENSION,
                'pricing_model' => Tool::PRICING_ONE_TIME,
                'price' => 9.99,
                'device_limit' => 1,
                'has_demo' => true,
                'demo_url' => 'https://demo.example.com',
            ])->assertStatus(201)
            ->assertJsonPath('data.has_demo', true)
            ->assertJsonPath('data.demo_url', 'https://demo.example.com');
    }
}
