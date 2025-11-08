<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePreferencesRequest extends FormRequest
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
            'preferences' => 'required|array',
            'preferences.sources' => 'nullable|array',
            'preferences.sources.*' => 'integer|exists:sources,id',
            'preferences.categories' => 'nullable|array',
            'preferences.categories.*' => 'integer|exists:categories,id',
            'preferences.authors' => 'nullable|array',
            'preferences.authors.*' => 'integer|exists:authors,id',
        ];
    }

    /**
     * Custom validation messages
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'preferences.required' => 'Preferences data is required.',
            'preferences.sources.*.exists' => 'One or more selected sources do not exist.',
            'preferences.categories.*.exists' => 'One or more selected categories do not exist.',
            'preferences.authors.*.exists' => 'One or more selected authors do not exist.',
        ];
    }
}
