<?php

namespace App\Http\Requests\School\Attendance;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class MarkStaffAttendanceRequest extends FormRequest
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
            'date' => 'required|date|before_or_equal:today',
            'records' => 'required|array|min:1',
            'records.*.staff_id' => 'required|exists:users,id',
            'records.*.status' => 'required|in:present,absent,half_day,on_leave',
            'records.*.check_in_time' => 'nullable|date_format:H:i',
            'records.*.check_out_time' => 'nullable|date_format:H:i',
            'records.*.remarks' => 'nullable|string|max:255',
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
            'date.required' => 'Attendance date is required.',
            'records.required' => 'Staff attendance records are required.',
        ];
    }
}
