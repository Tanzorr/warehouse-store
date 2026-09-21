<?php

namespace App\Http\Controllers\Swagger;

class TagController
{
    /**
     * @OA\Get(
     *     path="/api/tags",
     *     tags={"Tags"},
     *     security={{"bearerAuth": {}}},
     *     operationId="getTags",
     *     summary="Retrieve a list of tags",
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of tags",
     *
     *         @OA\JsonContent(
     *             type="array",
     *
     *             @OA\Items(ref="#/components/schemas/Tag")
     *         )
     *     )
     * )
     */
    public function index() {}

    /**
     * @OA\Post(
     *     path="/api/tags",
     *     tags={"Tags"},
     *     security={{"bearerAuth": {}}},
     *     operationId="createTag",
     *     summary="Create a new tag",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/Tag")
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Tag created successfully",
     *
     *         @OA\JsonContent(ref="#/components/schemas/Tag")
     *     )
     * )
     */
    public function store() {}

    /**
     * @OA\Get(
     *     path="/api/tags/{id}",
     *     tags={"Tags"},
     *     security={{"bearerAuth": {}}},
     *     operationId="getTagById",
     *     summary="Retrieve a specific tag by its ID",
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Tag details",
     *
     *         @OA\JsonContent(ref="#/components/schemas/Tag")
     *     )
     * )
     */
    public function show() {}

    /**
     * @OA\Put(
     *     path="/api/tags/{id}",
     *     tags={"Tags"},
     *     security={{"bearerAuth": {}}},
     *     operationId="updateTag",
     *     summary="Update an existing tag",
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/Tag")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Tag updated successfully",
     *
     *         @OA\JsonContent(ref="#/components/schemas/Tag")
     *     )
     * )
     */
    public function update() {}

    /**
     * @OA\Delete(
     *     path="/api/tags/{id}",
     *     tags={"Tags"},
     *     security={{"bearerAuth": {}}},
     *     operationId="deleteTag",
     *     summary="Delete a tag by its ID",
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=204,
     *         description="Tag deleted successfully"
     *     )
     * )
     */
    public function destroy() {}
}
