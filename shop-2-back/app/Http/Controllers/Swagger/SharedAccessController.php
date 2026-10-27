<?php

namespace App\Http\Controllers\Swagger;

class SharedAccessController
{
    /**
     * @OA\Get(
     *     path="/api/shared-accesses",
     *     tags={"SharedAccess"},
     *     summary="Get a list of all shared access records",
     *     security={{"bearerAuth": {}}},
     *     operationId="getSharedAccess",
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of shared access records",
     *
     *         @OA\JsonContent(
     *             type="array",
     *
     *             @OA\Items(ref="#/components/schemas/SharedAccess")
     *         )
     *     )
     * )
     */
    public function index() {}

    /**
     * @OA\Post(
     *     path="/api/shared-accesses",
     *     tags={"SharedAccess"},
     *     summary="Create a new shared access record",
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/StoreSharedAccessRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Shared access record created successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Shared access record created successfully")
     *         )
     *     )
     * )
     */
    public function store() {}

    /**
     * @OA\Put(
     *     path="/api/shared-accesses/{id}",
     *     tags={"SharedAccess"},
     *     summary="Update a shared access record",
     *     security={{"bearerAuth": {}}},
     *     operationId="updateSharedAccess",
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Shared access record ID",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/UpdateSharedAccessRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Shared access record updated successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Shared access record updated successfully")
     *         )
     *     )
     * )
     */
    public function update() {}

    /**
     * @OA\Delete(
     *     path="/api/shared-accesses/{id}",
     *     tags={"SharedAccess"},
     *     summary="Delete a shared access record",
     *     security={{"bearerAuth": {}}},
     *     operationId="deleteSharedAccess",
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Shared access record ID",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Shared access record deleted successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Shared access record deleted successfully")
     *         )
     *     )
     * )
     */
    public function destroy(string $id) {}
}
