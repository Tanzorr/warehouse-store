<?php

namespace App\Http\Controllers\Swagger;

class EntityMediaController
{
    /**
     * @OA\Post(
     *     path="/api/entities/media/attach",
     *     tags={"EntityMedia"},
     *     summary="Attach media to an entity",
     *     security={{"bearerAuth": {}}},
     *     operationId="attachMedia",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/EntityMedia")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Media attached successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Media attached successfully")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Invalid input"
     *     )
     * )
     */
    public function attach() {}

    /**
     * @OA\Post(
     *     path="/api/entities/media/detach",
     *     tags={"EntityMedia"},
     *     summary="Detach media from an entity",
     *     security={{"bearerAuth": {}}},
     *     operationId="detachMedia",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/EntityMedia")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Media detached successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Media detached successfully")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Invalid input"
     *     )
     * )
     */
    public function detach() {}
}
