<?php

namespace App\Http\Requests\Lms;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreHomeworkRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'class_id' => 'required|exists:academic_classes,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'submission_deadline' => 'required|date|after_or_equal:today',
            'description' => 'required|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,png,doc,docx,zip|max:10240',
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
            'submission_deadline.after_or_equal' => 'Submission deadline must be today or in the future.',
        ];
    }
}
