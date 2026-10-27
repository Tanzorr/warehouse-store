<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSharedAccessRequest extends FormRequest
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
}
