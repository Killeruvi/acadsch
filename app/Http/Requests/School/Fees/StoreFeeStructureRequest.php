<?php

namespace App\Http\Requests\School\Fees;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreFeeStructureRequest extends FormRequest
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
            'fee_type' => 'required|string|max:100',
            'amount' => 'required|numeric|min:0',
            'due_date' => 'nullable|date',
            'description' => 'nullable|string|max:500',
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
            'academic_class_id.required' => 'Please select an academic class for this fee structure.',
            'fee_type.required' => 'Fee title or category is required.',
            'amount.min' => 'Fee amount cannot be negative.',
        ];
    }
}
