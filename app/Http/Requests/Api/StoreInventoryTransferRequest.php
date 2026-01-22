<?php

namespace App\Http\Requests\Api;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryTransferRequest extends FormRequest
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
            'receiver_id' => [
                'required',
                'integer',
                'exists:users,id',
                Rule::notIn([auth()->id()]),
                // Verify receiver is a Tech
                function ($attribute, $value, $fail) {
                    $receiver = \App\Models\User::find($value);
                    if (!$receiver || $receiver->role !== UserRole::Tech) {
                        $fail('Receiver must be a technician.');
                    }
                },
            ],
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
            'receiver_id.required' => 'Receiver is required.',
            'receiver_id.exists' => 'Selected receiver does not exist.',
            'receiver_id.not_in' => 'Cannot transfer inventory to yourself.',
            'items.required' => 'At least one item is required.',
            'items.*.item_id.exists' => 'Selected inventory item does not exist.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
        ];
    }
}
