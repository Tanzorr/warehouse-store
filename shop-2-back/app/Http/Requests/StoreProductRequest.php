<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
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
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category_id' => [
                'required',
                'integer',
                'exists:categories,id',
            ],
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'purchase_price' => [
                'required',
                'numeric',
                'min:0',
            ],
            'sale_price' => [
                'required',
                'numeric',
                'min:0.01',
                'gt:purchase_price',
            ],
            'stock' => [
                'required',
                'integer',
                'min:0',
            ],
            'sku' => [
                'required',
                'string',
                'max:50',
                Rule::unique('products', 'sku'),
            ],
            'tags' => 'array',
            'tags.*' => 'string',
        ];
    }
}
