<?php

namespace App\Http\Requests\School\Fees;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMonthlyConfigRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'auto_enabled' => 'nullable',
            'default_amount' => 'required|numeric|min:1',
            'due_day' => 'required|integer|min:1|max:28',
            'category' => 'required|string|max:100',
            'title_template' => 'required|string|max:255',
            'notes_template' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'default_amount.required' => 'Default monthly tuition fee amount is required.',
            'due_day.between' => 'Due day must be between the 1st and 28th of the month.',
        ];
    }
}
