<?php

namespace App\Http\Requests\School\Students;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSchoolStudentRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'class_id' => 'required|exists:academic_classes,id',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|string|in:male,female,other',
            'phone' => 'nullable|string|max:30',
            'guardian_name' => 'nullable|string|max:255',
            'guardian_phone' => 'nullable|string|max:30',
            'guardian_email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'blood_group' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'admission_number' => 'nullable|string|max:50|unique:users,student_id_number',
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
            'name.required' => 'Student full name is required.',
            'email.unique' => 'This login email is already allocated to another user.',
            'class_id.required' => 'Please assign the student to an academic class.',
        ];
    }
}
