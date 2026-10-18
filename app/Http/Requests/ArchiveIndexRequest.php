<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ArchiveIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['completed', 'released', 'rejected'])],
            'dept' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'order' => ['nullable', Rule::in(['asc', 'desc'])],
            'print' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Choose a valid archived status.',
            'date_from.date_format' => 'The start date must be a valid date.',
            'date_to.date_format' => 'The end date must be a valid date.',
            'date_to.after_or_equal' => 'The end date must be on or after the start date.',
            'order.in' => 'Choose a valid archive sort order.',
        ];
    }
}
