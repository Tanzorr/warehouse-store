<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSharedAccessRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // You can implement authorization logic here, e.g., check user roles
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'accessible_type' => 'required|string',
            'accessible_id' => 'required|integer|exists:categories,id',
            'user_id' => 'required|integer|exists:users,id',
            'expires_at' => 'nullable|date|after_or_equal:now',
        ];
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'accessible_type.required' => 'The accessible type is required.',
            'accessible_type.in' => 'The accessible type must be a valid model.',
            'accessible_id.required' => 'The accessible ID is required.',
            'accessible_id.exists' => 'The specified resource does not exist.',
            'user_id.required' => 'The user ID is required.',
            'user_id.exists' => 'The specified user does not exist.',
            'expires_at.after_or_equal' => 'The expiration date must be today or in the future.',
        ];
    }
}
