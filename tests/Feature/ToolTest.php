<?php

namespace Tests\Feature;

use App\Models\Tool;
use Database\Factories\ToolFactory;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_tool_can_be_created_via_factory(): void
    {
        $tool = Tool::factory()->create([
            'name' => 'TimeSync',
            'slug' => 'timesync',
        ]);

        $this->assertDatabaseHas('tools', [
            'id' => $tool->id,
            'name' => 'TimeSync',
            'slug' => 'timesync',
        ]);
        $this->assertInstanceOf(ToolFactory::class, Tool::factory());
    }

    public function test_tool_defaults_are_applied(): void
    {
        $tool = Tool::factory()->create();

        $this->assertEquals('extension', $tool->type);
        $this->assertEquals('one_time', $tool->pricing_model);
        $this->assertEquals(1, $tool->device_limit);
        $this->assertEquals(0, (int) $tool->referral_credits);
        $this->assertTrue($tool->is_active);
    }

    public function test_tool_has_many_tool_files(): void
    {
        $tool = Tool::factory()->create();

        $tool->files()->create([
            'file_path' => 'tools/1/timesync-v1.0.0.zip',
            'file_type' => 'zip',
            'version' => '1.0.0',
        ]);
        $tool->files()->create([
            'file_path' => 'tools/1/timesync-v1.0.0.exe',
            'file_type' => 'exe',
            'version' => '1.0.0',
        ]);

        $this->assertCount(2, $tool->files);
        $this->assertTrue($tool->files->every(fn ($file) => $file->tool_id === $tool->id));
    }

    public function test_tool_slug_is_unique(): void
    {
        Tool::factory()->create(['slug' => 'timesync']);

        $this->expectException(QueryException::class);

        Tool::factory()->create(['slug' => 'timesync']);
    }
}
