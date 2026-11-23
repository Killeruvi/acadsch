<?php

namespace App\Http\Requests\School\Academics;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAcademicClassRequest extends FormRequest
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
            'name' => 'required|string|max:100',
            'grade_level' => 'required|integer|min:1|max:12',
            'section' => 'nullable|string|max:10',
            'academic_session' => 'nullable|string|max:50',
            'stream' => 'nullable|string|max:100',
            'class_teacher_id' => 'nullable|exists:users,id',
            'room_number' => 'nullable|string|max:50',
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
            'name.required' => 'Class display name is required.',
            'grade_level.required' => 'Grade level numeric rank is required.',
        ];
    }
}
