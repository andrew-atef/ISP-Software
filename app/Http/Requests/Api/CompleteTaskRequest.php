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
            'notes' => ['nullable', 'string'],
            'serials' => ['nullable', 'array'],
            'serials.ont_serial' => ['nullable', 'string'],
            'serials.eero_serial_1' => ['nullable', 'string'],
            'serials.eero_serial_2' => ['nullable', 'string'],
            'serials.eero_serial_3' => ['nullable', 'string'],
            'inventory_used' => ['nullable', 'array'],
            'inventory_used.*.item_id' => ['required', 'integer', 'exists:inventory_items,id'],
            'inventory_used.*.quantity' => ['required', 'integer', 'min:1'],
            'timestamp' => ['nullable', 'date'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Flatten nested 'serials' object if present
        if ($this->has('serials') && is_array($this->serials)) {
            foreach ($this->serials as $key => $value) {
                if (!$this->has($key)) {
                    $this->merge([$key => $value]);
                }
            }
        }

        // Map 'notes' to 'tech_notes' if 'tech_notes' is not provided
        if ($this->has('notes') && !$this->has('tech_notes')) {
            $this->merge(['tech_notes' => $this->notes]);
        }
    }
}
