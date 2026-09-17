<?php

namespace App\Console\Commands;

use App\Models\Tool;
use App\Services\ExtensionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PackageExtensions extends Command
{
    protected $signature = 'extensions:package {tool? : Tool slug. Packages all extension tools when omitted}';

    protected $description = 'Build distributable extension zip files with injected manifests';

    public function handle(ExtensionService $extensions): int
    {
        $tools = ($slug = $this->argument('tool'))
            ? Tool::query()->where('slug', $slug)->get()
            : Tool::query()->where('type', Tool::TYPE_EXTENSION)->get();

        if ($tools->isEmpty()) {
            $this->error('No extension tools found.');

            return self::FAILURE;
        }

        foreach ($tools as $tool) {
            $browsers = $tool->extension_meta['browsers'] ?? ['chrome'];

            foreach ($browsers as $browser) {
                try {
                    $path = $extensions->packageToPath($tool, $browser);
                    $this->info("Packaged [{$tool->slug}] for {$browser} -> ".Storage::disk('local')->path($path));
                } catch (\RuntimeException $e) {
                    $this->error($e->getMessage());
                }
            }
        }

        return self::SUCCESS;
    }
}
