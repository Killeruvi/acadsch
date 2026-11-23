<?php

namespace App\Http\Requests\School\Transport;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTransportRouteRequest extends FormRequest
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
            'title' => 'required|string|max:150|unique:transport_routes,title',
            'vehicle_id' => 'nullable|exists:transport_vehicles,id',
            'monthly_fare' => 'required|numeric|min:0',
            'start_point' => 'required|string|max:150',
            'end_point' => 'required|string|max:150',
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
            'title.required' => 'Route title is required.',
            'monthly_fare.min' => 'Monthly transport fare cannot be negative.',
        ];
    }
}
