<?php

namespace App\Http\Resources;

use App\Models\Tool;
use App\Models\ToolFile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Tool */
class ToolResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'type' => $this->type,
            'pricing_model' => $this->pricing_model,
            'price' => $this->price,
            'device_limit' => $this->device_limit,
            'referral_credits' => $this->referral_credits,
            'icon' => $this->icon,
            'has_demo' => $this->has_demo,
            'demo_url' => $this->demo_url,
            'is_active' => $this->is_active,
            'files' => $this->whenLoaded('files', fn ($files) => $files->map(
                fn (ToolFile $file) => [
                    'id' => $file->id,
                    'file_path' => $file->file_path,
                    'file_type' => $file->file_type,
                    'version' => $file->version,
                    'changelog' => $file->changelog,
                ]
            )),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
