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
     * Prepare input data before validation.
     */
    protected function prepareForValidation(): void
    {
        // Extract city name from full address if it contains parentheses
        $location = $this->pickup_location;
        if (strpos($location, ')') !== false) {
            // Extract city from format like "(Region 1) 375 Fentress Blvd, Daytona Beach, FL 32114"
            // First, find the city name by checking common locations
            $cities = ['Daytona Beach', 'Melbourne', 'Fort Pierce'];
            foreach ($cities as $city) {
                if (strpos($location, $city) !== false) {
                    $this->merge(['pickup_location' => $city]);
                    break;
                }
            }
        }
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
            'items.*.item_id' => ['nullable', 'integer'],
            'items.*.name' => ['required_if:items.*.item_id,0', 'string'],
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
            'pickup_location.in' => 'Pickup location must be one of: (Region 1) Daytona Beach, (Region 2) Melbourne, (Region 3) Fort Pierce.',
            'items.required' => 'At least one item is required.',
            'items.*.name.required_if' => 'Item name is required when item_id is 0 or unknown.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
        ];
    }
}

