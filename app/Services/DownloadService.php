<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\ToolFile;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class DownloadService
{
    /**
     * Verify a purchase and build the injected configuration payload.
     *
     * @return array{file: ToolFile, config: array<string, mixed>}
     */
    public function download(User $user, ToolFile $file): array
    {
        $purchase = Purchase::query()
            ->where('user_id', $user->id)
            ->where('tool_id', $file->tool_id)
            ->where('status', Purchase::STATUS_ACTIVE)
            ->latest()
            ->first();

        if (! $purchase) {
            throw new HttpResponseException(
                response()->json(['message' => 'payment_required'], 403),
            );
        }

        $config = [
            'token' => Crypt::decryptString($purchase->licenseToken->token_value),
            'api_url' => rtrim(config('app.url'), '/'),
            'tool_id' => $file->tool_id,
        ];

        return ['file' => $file, 'config' => $config];
    }

    /**
     * Produce a copy of the zip with config.json injected at its root.
     */
    public function bundleZip(ToolFile $file, array $config): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'bundle');

        copy(Storage::disk('local')->path($file->file_path), $tmp);

        $zip = new ZipArchive;
        $zip->open($tmp);
        $zip->addFromString('config.json', $this->encodeConfig($config));
        $zip->close();

        return $tmp;
    }

    private function encodeConfig(array $config): string
    {
        return json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
