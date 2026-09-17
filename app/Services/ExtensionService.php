<?php

namespace App\Services;

use App\Models\Tool;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ExtensionService
{
    public const BROWSERS = ['chrome', 'firefox', 'edge'];

    /**
     * Build the browser manifest for the tool.
     *
     * @return array<string, mixed>
     */
    public function manifest(Tool $tool, string $browser): array
    {
        $browser = in_array($browser, self::BROWSERS) ? $browser : 'chrome';
        $meta = $tool->extension_meta ?? [];
        $version = $tool->files()->latest('id')->first()?->version ?? '1.0.0';

        $manifest = [
            'manifest_version' => (int) ($meta['manifest_version'] ?? 3),
            'name' => $tool->name,
            'version' => $version,
            'description' => $tool->description,
            'permissions' => $meta['permissions'] ?? [],
        ];

        if ($browser === 'firefox') {
            $manifest['manifest_version'] = 2;
            $manifest['background'] = ['scripts' => ['background.js']];
            $manifest['browser_action'] = ['default_popup' => 'popup.html'];
            $manifest['browser_specific_settings'] = [
                'gecko' => [
                    'id' => "{$tool->slug}@tools.example",
                    'strict_min_version' => '115.0',
                ],
            ];
        } else {
            $manifest['background'] = ['service_worker' => 'background.js'];
            $manifest['action'] = ['default_popup' => 'popup.html'];
        }

        return $manifest;
    }

    /**
     * Build a standalone package with the manifest (no customer config) at
     * storage/app/private/extensions and return the Storage path.
     */
    public function packageToPath(Tool $tool, string $browser): string
    {
        $source = $tool->files()->latest('id')->first()?->file_path;

        if (! $source || ! Storage::disk('local')->exists($source)) {
            throw new \RuntimeException("No file uploaded for tool [{$tool->slug}].");
        }

        $version = $tool->files()->latest('id')->first()->version ?? '1.0.0';
        $path = "extensions/{$tool->slug}-{$browser}-{$version}.zip";

        $tmp = tempnam(sys_get_temp_dir(), 'extpkg');
        copy(Storage::disk('local')->path($source), $tmp);

        $zip = new ZipArchive;
        $zip->open($tmp);
        $zip->addFromString('manifest.json', $this->encode($this->manifest($tool, $browser)));
        $zip->addFromString('extension.json', $this->encode($tool->extension_meta ?? []));
        $zip->close();

        Storage::disk('local')->put($path, file_get_contents($tmp));
        @unlink($tmp);

        return $path;
    }

    private function encode(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
