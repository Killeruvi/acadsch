<?php

namespace App\Http\Requests\School\Transport;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTransportVehicleRequest extends FormRequest
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
            'vehicle_number' => 'required|string|max:50|unique:transport_vehicles,vehicle_number',
            'capacity' => 'required|integer|min:1|max:150',
            'model' => 'nullable|string|max:100',
            'driver_name' => 'nullable|string|max:150',
            'driver_phone' => 'nullable|string|max:30',
            'driver_license' => 'nullable|string|max:100',
            'status' => 'nullable|string|in:active,maintenance,retired',
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
            'vehicle_number.unique' => 'This vehicle registration number is already on file.',
            'capacity.min' => 'Passenger capacity must be at least 1.',
        ];
    }
}
