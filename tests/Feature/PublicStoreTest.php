<?php

namespace Tests\Feature;

use App\Models\Tool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_tools_endpoint_is_accessible_without_auth(): void
    {
        Tool::factory()->create(['is_active' => true]);

        $this->getJson('/api/tools')
            ->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    public function test_public_endpoint_returns_active_tools_only(): void
    {
        Tool::factory()->create(['name' => 'Active Tool', 'is_active' => true]);
        Tool::factory()->create(['name' => 'Draft Tool', 'is_active' => false]);

        $response = $this->getJson('/api/tools')->assertOk();

        $names = collect($response->json('data'))->pluck('name');

        $this->assertContains('Active Tool', $names);
        $this->assertNotContains('Draft Tool', $names);
    }

    public function test_public_endpoint_exposes_store_fields(): void
    {
        Tool::factory()->create([
            'name' => 'TimeSync',
            'slug' => 'timesync',
            'type' => Tool::TYPE_EXTENSION,
            'pricing_model' => Tool::PRICING_ONE_TIME,
            'price' => 19.99,
            'device_limit' => 2,
            'has_demo' => true,
            'demo_url' => 'https://demo.example.com',
            'is_active' => true,
        ]);

        $this->getJson('/api/tools')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'TimeSync')
            ->assertJsonPath('data.0.price', '19.99')
            ->assertJsonPath('data.0.pricing_model', Tool::PRICING_ONE_TIME)
            ->assertJsonPath('data.0.type', Tool::TYPE_EXTENSION)
            ->assertJsonPath('data.0.has_demo', true)
            ->assertJsonPath('data.0.demo_url', 'https://demo.example.com');
    }

    public function test_public_endpoint_orders_latest_first(): void
    {
        $older = Tool::factory()->create(['name' => 'Older', 'is_active' => true, 'created_at' => now()->subDays(5)]);
        $newer = Tool::factory()->create(['name' => 'Newer', 'is_active' => true, 'created_at' => now()->subDay()]);

        $this->assertTrue($older->created_at->lt($newer->created_at));

        $this->getJson('/api/tools')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Newer');
    }
}
