<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitRequisitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'request_type' => ['required', 'in:minor,major'],
            'purpose' => ['required', 'string', 'min:10', 'max:500'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.supply_id' => ['required', 'integer', 'exists:supplies,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.options' => ['nullable', 'array', 'max:5'],
            'items.*.options.*' => ['nullable', 'string', 'max:100'],
        ];
    }
}
