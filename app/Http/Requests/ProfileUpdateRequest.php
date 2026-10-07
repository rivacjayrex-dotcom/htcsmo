<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'min:4',
                'max:30',
                'alpha_dash:ascii',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
        ];
    }

    /**
     * Normalize profile values before validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => preg_replace('/\s+/', ' ', trim((string) $this->name)),
            'username' => strtolower(trim((string) $this->username)),
            'email' => strtolower(trim((string) $this->email)),
        ]);
    }
}
