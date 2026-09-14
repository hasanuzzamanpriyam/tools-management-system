<?php

namespace Tests\Feature\Admin;

use App\Models\Tool;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ToolFileUploadTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsRole(string $role): User
    {
        $user = User::factory()->create(['role' => $role]);

        $this->actingAs($user, 'sanctum');

        return $user;
    }

    private function createTool(): Tool
    {
        return Tool::factory()->create(['slug' => 'timesync']);
    }

    public function test_admin_can_upload_zip_file(): void
    {
        Storage::fake('local');
        $this->actingAsRole(User::ROLE_ADMIN);
        $tool = $this->createTool();

        $response = $this->post("/api/admin/tools/{$tool->id}/files", [
            'file' => UploadedFile::fake()->create('timesync-v1.0.0.zip', 2048, 'application/zip'),
            'version' => '1.0.0',
            'changelog' => 'Initial release.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('file_type', 'zip')
            ->assertJsonPath('version', '1.0.0');
        $this->assertDatabaseHas('tool_files', [
            'tool_id' => $tool->id,
            'file_type' => 'zip',
            'version' => '1.0.0',
        ]);
        Storage::disk('local')->assertExists("tools/{$tool->id}/timesync-v1.0.0.zip");
    }

    public function test_admin_can_upload_exe_file(): void
    {
        Storage::fake('local');
        $this->actingAsRole(User::ROLE_ADMIN);
        $tool = $this->createTool();

        $this->post("/api/admin/tools/{$tool->id}/files", [
            'file' => UploadedFile::fake()->create('timesync-setup.exe', 4096, 'application/x-msdownload'),
            'version' => '1.0.0',
        ])->assertStatus(201)
            ->assertJsonPath('file_type', 'exe');

        $this->assertDatabaseHas('tool_files', [
            'tool_id' => $tool->id,
            'file_type' => 'exe',
        ]);
    }

    public function test_regular_user_cannot_upload_file(): void
    {
        $this->actingAsRole(User::ROLE_USER);
        $tool = $this->createTool();

        $this->post("/api/admin/tools/{$tool->id}/files", [
            'file' => UploadedFile::fake()->create('timesync.zip', 1024, 'application/zip'),
            'version' => '1.0.0',
        ])->assertStatus(403);
    }

    public function test_guest_cannot_upload_file(): void
    {
        $tool = $this->createTool();

        $this->post("/api/admin/tools/{$tool->id}/files", [
            'file' => UploadedFile::fake()->create('timesync.zip', 1024, 'application/zip'),
            'version' => '1.0.0',
        ])->assertStatus(401);
    }

    public function test_upload_rejects_invalid_file_type(): void
    {
        Storage::fake('local');
        $this->actingAsRole(User::ROLE_ADMIN);
        $tool = $this->createTool();

        $this->post("/api/admin/tools/{$tool->id}/files", [
            'file' => UploadedFile::fake()->create('notes.txt', 1024),
            'version' => '1.0.0',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['file']);

        $this->assertDatabaseCount('tool_files', 0);
    }

    public function test_upload_requires_version(): void
    {
        Storage::fake('local');
        $this->actingAsRole(User::ROLE_ADMIN);
        $tool = $this->createTool();

        $this->post("/api/admin/tools/{$tool->id}/files", [
            'file' => UploadedFile::fake()->create('timesync.zip', 1024, 'application/zip'),
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['version']);

        $this->assertDatabaseCount('tool_files', 0);
    }

    public function test_admin_can_delete_tool_file(): void
    {
        Storage::fake('local');
        $this->actingAsRole(User::ROLE_ADMIN);
        $tool = $this->createTool();
        $toolFile = $tool->files()->create([
            'file_path' => "tools/{$tool->id}/timesync-v1.0.0.zip",
            'file_type' => 'zip',
            'version' => '1.0.0',
        ]);
        Storage::disk('local')->put($toolFile->file_path, 'payload');

        $this->delete("/api/admin/tools/{$tool->id}/files/{$toolFile->id}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('tool_files', ['id' => $toolFile->id]);
        Storage::disk('local')->assertMissing($toolFile->file_path);
    }
}
