<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SystemPromptStoreRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'prompt' => ['required', 'string', 'min:10'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
            'order' => ['integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The system prompt name is required.',
            'name.max' => 'The system prompt name must not exceed 255 characters.',
            'prompt.required' => 'The prompt content is required.',
            'prompt.min' => 'The prompt must be at least 10 characters long.',
            'description.max' => 'The description must not exceed 1000 characters.',
            'order.min' => 'The order must be a positive number.',
        ];
    }
}
