<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexArticleRequest extends FormRequest
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
            'search' => 'nullable|string|max:255',
            'filter' => 'nullable|array',
            'filter.date_from' => 'nullable|date',
            'filter.date_to' => 'nullable|date|after_or_equal:filter.date_from',
            'filter.source' => 'nullable|string|max:100',
            'filter.category' => 'nullable|string|max:100',
            'filter.author' => 'nullable|string|max:100',
            'filter.tag' => 'nullable|string|max:100',
            'include' => 'nullable|string',
            'per_page' => 'nullable|integer|min:1|max:100',
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
            'filter.date_to.after_or_equal' => 'The end date must be after or equal to the start date.',
            'per_page.min' => 'Items per page must be at least 1.',
            'per_page.max' => 'Items per page cannot exceed 100.',
        ];
    }
}
