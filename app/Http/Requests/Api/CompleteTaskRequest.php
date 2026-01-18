<?php

namespace App\Http\Requests\Api;

use App\Enums\InstallationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization checked in controller
    }

    public function rules(): array
    {
        return [
            'end_lat' => ['required', 'numeric', 'between:-90,90'],
            'end_lng' => ['required', 'numeric', 'between:-180,180'],
            'installation_type' => ['required', Rule::enum(InstallationType::class)],
            'drop_bury_status' => ['required', 'boolean'],
            'sidewalk_bore_status' => ['required', 'boolean'],
            'ont_serial' => ['nullable', 'string', 'max:255'],
            'eero_serial_1' => ['nullable', 'string', 'max:255'],
            'eero_serial_2' => ['nullable', 'string', 'max:255'],
            'eero_serial_3' => ['nullable', 'string', 'max:255'],
            'tech_notes' => ['nullable', 'string'],
            'inventory_used' => ['nullable', 'array'],
            'inventory_used.*.item_id' => ['required', 'integer', 'exists:inventory_items,id'],
            'inventory_used.*.quantity' => ['required', 'integer', 'min:1'],
            'timestamp' => ['nullable', 'date'],
        ];
    }
}
