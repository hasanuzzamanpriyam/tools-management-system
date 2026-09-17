<?php

namespace App\Http\Requests;

use App\Models\Tool;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ToolUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('tools')->ignore($this->route('tool'))],
            'description' => ['required', 'string'],
            'type' => ['required', Rule::in([Tool::TYPE_EXTENSION, Tool::TYPE_DESKTOP])],
            'pricing_model' => ['required', Rule::in([Tool::PRICING_ONE_TIME, Tool::PRICING_SUBSCRIPTION])],
            'price' => ['required', 'numeric', 'min:0'],
            'device_limit' => ['required', 'integer', 'min:1'],
            'referral_credits' => ['nullable', 'numeric', 'min:0'],
            'icon' => ['nullable', 'string', 'max:255'],
            'has_demo' => ['nullable', 'boolean'],
            'demo_url' => ['nullable', 'url', 'max:2048'],
            'extension_meta' => ['nullable', 'array'],
            'extension_meta.browsers' => ['nullable', 'array'],
            'extension_meta.browsers.*' => ['required', Rule::in(['chrome', 'firefox', 'edge'])],
            'extension_meta.manifest_version' => ['nullable', 'integer', 'in:2,3'],
            'extension_meta.permissions' => ['nullable', 'array'],
            'extension_meta.permissions.*' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
