<?php

namespace App\Http\Requests;

use App\Models\Tool;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ToolStoreRequest extends FormRequest
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
            'slug' => ['required', 'string', 'max:255', Rule::unique('tools')],
            'description' => ['required', 'string'],
            'type' => ['required', Rule::in([Tool::TYPE_EXTENSION, Tool::TYPE_DESKTOP])],
            'pricing_model' => ['required', Rule::in([Tool::PRICING_ONE_TIME, Tool::PRICING_SUBSCRIPTION])],
            'price' => ['required', 'numeric', 'min:0'],
            'device_limit' => ['required', 'integer', 'min:1'],
            'referral_credits' => ['nullable', 'numeric', 'min:0'],
            'icon' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
