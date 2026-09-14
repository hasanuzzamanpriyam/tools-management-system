<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ToolFileUploadRequest extends FormRequest
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
            'file' => ['required', 'file', 'max:102400', 'mimes:zip,exe,msi'],
            'version' => ['required', 'string', 'max:50'],
            'changelog' => ['nullable', 'string'],
        ];
    }
}
