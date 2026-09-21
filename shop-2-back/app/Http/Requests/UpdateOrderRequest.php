<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'sometimes|in:pending,processing,shipped,delivered,canceled',
            'payment_status' => 'sometimes|in:pending,paid,failed',
            'total_price' => 'sometimes|numeric|min:0',
            'shipping_address' => 'sometimes|string',
            'billing_address' => 'sometimes|string|nullable',
            'notes' => 'sometimes|string|nullable',
        ];
    }
}
