<?php

namespace App\Http\Controllers;

use App\Actions\AttachMediaAction;
use App\Actions\DetachMediaAction;
use App\Http\Requests\AttachMediaRequest;
use App\Http\Requests\DetachMediaRequest;
use Illuminate\Http\JsonResponse;

class EntityMediaController extends Controller
{
    public function __construct(
        private AttachMediaAction $attachMediaAction,
        private DetachMediaAction $detachMediaAction
    ) {}

    public function attach(AttachMediaRequest $request): JsonResponse
    {
        try {
            $attachedEntity = $this->attachMediaAction->handle($request->validated());
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        return response()->json($attachedEntity, 200);
    }

    public function detach(DetachMediaRequest $request): JsonResponse
    {
        if ($this->detachMediaAction->handle($request->validated())) {
            return response()->json(['message' => 'Media detached successfully'], 200);
        }

        return response()->json(['message' => 'Media not detached'], 400);
    }
}
