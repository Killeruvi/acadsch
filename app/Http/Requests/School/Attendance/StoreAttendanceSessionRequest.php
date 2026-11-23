<?php

namespace App\Http\Requests\School\Attendance;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceSessionRequest extends FormRequest
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
            'class_id' => 'required|exists:academic_classes,id',
            'session_date' => 'required|date|before_or_equal:today',
            'title' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'attendance' => 'required|array|min:1',
            'attendance.*' => 'required|in:present,absent,late,half_day,excused',
            'correction_reason' => 'nullable|string|max:500',
            'period_number' => 'nullable|integer|min:1',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
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
            'class_id.required' => 'Academic class is required.',
            'attendance.required' => 'Attendance roll call entries are required for all students.',
            'session_date.before_or_equal' => 'Attendance date cannot be in the future.',
        ];
    }
}
