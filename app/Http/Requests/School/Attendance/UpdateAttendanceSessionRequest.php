<?php

namespace App\Http\Requests\School\Attendance;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAttendanceSessionRequest extends FormRequest
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
            'session_date' => 'required|date|before_or_equal:today',
            'title' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'attendance' => 'required|array',
            'attendance.*' => 'required|in:present,absent,late,half_day,excused',
            'correction_reason' => 'nullable|string|max:500',
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
            'attendance.required' => 'Attendance entries must be supplied.',
        ];
    }
}
