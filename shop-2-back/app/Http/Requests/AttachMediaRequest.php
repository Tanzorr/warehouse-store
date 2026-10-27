<?php

namespace App\Http\Requests;

use App\Models\MediaRelation;
use App\Traits\ResolvesEntities;
use Illuminate\Foundation\Http\FormRequest;

class AttachMediaRequest extends FormRequest
{
    use ResolvesEntities;

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
            'media_id' => [
                'required',
                'exists:media,id',
                $this->mediaAlreadyAttachedRule(),
            ],
            'mediable_type' => 'required|string',
            'mediable_id' => 'required|integer',
        ];
    }

    /**
     * Custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'media_id.required' => 'The media ID is required.',
            'media_id.exists' => 'The selected media does not exist.',
            'mediable_type.required' => 'The entity type is required.',
            'mediable_id.required' => 'The entity ID is required.',
        ];
    }

    private function mediaAlreadyAttachedRule(): \Closure
    {
        return function ($attribute, $value, $fail) {
            if (MediaRelation::where('media_id', $this->media_id)
                ->where('mediable_type', $this->entityMap[$this->mediable_type])
                ->where('mediable_id', $this->mediable_id)->exists()
            ) {
                $fail('This media is already attached to the entity.');
            }
        };
    }
}
