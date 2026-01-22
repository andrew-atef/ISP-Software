<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'pickup_date' => ['required', 'date', 'after_or_equal:today'],
            'pickup_location' => ['required', 'string', Rule::in(['Daytona Beach', 'Melbourne', 'Fort Pierce'])],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'integer', 'exists:inventory_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'pickup_date.required' => 'Pickup date is required.',
            'pickup_date.date' => 'Pickup date must be a valid date.',
            'pickup_date.after_or_equal' => 'Pickup date must be today or in the future.',
            'pickup_location.required' => 'Pickup location is required.',
            'pickup_location.in' => 'Pickup location must be one of: Daytona Beach, Melbourne, Fort Pierce.',
            'items.required' => 'At least one item is required.',
            'items.*.item_id.exists' => 'Selected inventory item does not exist.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
        ];
    }
}
