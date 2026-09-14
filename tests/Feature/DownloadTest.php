<?php

namespace Tests\Feature;

use App\Models\Purchase;
use App\Models\Tool;
use App\Models\ToolFile;
use App\Models\User;
use App\Services\TokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class DownloadTest extends TestCase
{
    use RefreshDatabase;

    private function makeZipFile(Tool $tool): ToolFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'src');
        $zip = new ZipArchive;
        $zip->open($tmp, ZipArchive::OVERWRITE);
        $zip->addFromString('readme.txt', 'hello world');
        $zip->close();

        Storage::disk('local')->putFileAs("tools/{$tool->id}", $tmp, 'bundle.zip');

        return ToolFile::query()->create([
            'tool_id' => $tool->id,
            'file_path' => "tools/{$tool->id}/bundle.zip",
            'file_type' => 'zip',
            'version' => '1.0.0',
            'changelog' => null,
        ]);
    }

    private function makeExeFile(Tool $tool): ToolFile
    {
        Storage::disk('local')->put("tools/{$tool->id}/installer.exe", 'fake-exe-bytes');

        return ToolFile::query()->create([
            'tool_id' => $tool->id,
            'file_path' => "tools/{$tool->id}/installer.exe",
            'file_type' => 'exe',
            'version' => '1.0.0',
            'changelog' => null,
        ]);
    }

    private function buy(Tool $tool, User $user): string
    {
        $purchase = Purchase::factory()->create([
            'user_id' => $user->id,
            'tool_id' => $tool->id,
            'status' => Purchase::STATUS_ACTIVE,
        ]);

        return (new TokenService)->generate($purchase);
    }

    public function test_non_purchaser_is_forbidden(): void
    {
        $tool = Tool::factory()->create();
        $this->makeZipFile($tool);
        $nonPurchaser = User::factory()->create();

        $this->actingAs($nonPurchaser)
            ->getJson("/api/tools/{$tool->id}/download")
            ->assertStatus(403)
            ->assertJson(['message' => 'payment_required']);
    }

    public function test_tool_without_file_returns_not_found(): void
    {
        $tool = Tool::factory()->create();
        $purchaser = User::factory()->create();
        Purchase::factory()->create([
            'user_id' => $purchaser->id,
            'tool_id' => $tool->id,
            'status' => Purchase::STATUS_ACTIVE,
        ]);

        $this->actingAs($purchaser)
            ->getJson("/api/tools/{$tool->id}/download")
            ->assertStatus(404);
    }

    public function test_unauthenticated_request_is_redirected_to_login(): void
    {
        $tool = Tool::factory()->create();

        $this->getJson("/api/tools/{$tool->id}/download")
            ->assertStatus(401);
    }

    public function test_purchaser_receives_zip_with_injected_config(): void
    {
        [$purchaser] = [User::factory()->create()];
        $tool = Tool::factory()->create();
        $file = $this->makeZipFile($tool);
        $token = $this->buy($tool, $purchaser);

        $response = $this->actingAs($purchaser)
            ->get("/api/tools/{$tool->id}/download")
            ->assertOk()
            ->assertDownload('bundle.zip');

        $tmp = tempnam(sys_get_temp_dir(), 'dl');
        file_put_contents($tmp, $response->streamedContent());
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($tmp) === true, 'Download is a valid zip archive');
        $this->assertSame('hello world', $zip->getFromName('readme.txt'));

        $config = json_decode($zip->getFromName('config.json'), true);
        $this->assertIsArray($config);
        $this->assertSame($token, $config['token']);
        $this->assertSame($tool->id, $config['tool_id']);
        $this->assertSame(rtrim(config('app.url'), '/'), $config['api_url']);

        $zip->close();
        @unlink($tmp);
        @unlink($file->file_path);
    }

    public function test_non_zip_file_streams_with_separate_config_download(): void
    {
        $purchaser = User::factory()->create();
        $tool = Tool::factory()->create();
        $this->makeExeFile($tool);
        $token = $this->buy($tool, $purchaser);

        $response = $this->actingAs($purchaser)
            ->get("/api/tools/{$tool->id}/download")
            ->assertOk()
            ->assertDownload('installer.exe');

        $this->assertSame('fake-exe-bytes', $response->streamedContent());

        $configResponse = $this->actingAs($purchaser)
            ->get("/api/tools/{$tool->id}/download/config")
            ->assertOk()
            ->assertDownload('config.json');

        $config = json_decode($configResponse->streamedContent(), true);
        $this->assertIsArray($config);
        $this->assertSame($token, $config['token']);
        $this->assertSame($tool->id, $config['tool_id']);
    }
}
