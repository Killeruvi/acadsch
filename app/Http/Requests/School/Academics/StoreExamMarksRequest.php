<?php

namespace App\Http\Requests\School\Academics;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreExamMarksRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->isAdmin() || auth()->user()->isTeacher());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'schedule_id' => 'required|exists:school_exam_schedules,id',
            'marks' => 'required|array|min:1',
            'marks.*.student_id' => 'required|exists:users,id',
            'marks.*.marks_obtained' => 'nullable|numeric|min:0',
            'marks.*.is_absent' => 'nullable|boolean',
            'marks.*.remarks' => 'nullable|string|max:255',
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
            'marks.required' => 'Marks submission must contain student evaluation scores.',
        ];
    }
}
