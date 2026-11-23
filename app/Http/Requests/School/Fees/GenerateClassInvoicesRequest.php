<?php

namespace App\Http\Requests\School\Fees;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class GenerateClassInvoicesRequest extends FormRequest
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
            'academic_class_id' => 'required|exists:academic_classes,id',
            'due_date' => 'required|date',
            'fee_structure_ids' => 'nullable|array',
            'fee_structure_ids.*' => 'exists:school_fee_structures,id',
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
            'academic_class_id.required' => 'Please select the target class to bill.',
            'due_date.required' => 'A valid payment due date is required.',
        ];
    }
}
