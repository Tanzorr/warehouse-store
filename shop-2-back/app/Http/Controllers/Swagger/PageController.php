<?php

namespace App\Http\Controllers\Swagger;

class PageController
{
    /**
     * @OA\Get(
     *     path="/api/pages",
     *     summary="Get all pages",
     *     tags={"Page"},
     *     security={{"bearerAuth": {}}},
     *
     *      @OA\Response(
     *      response=200,
     *      description="List of pages",
     *
     *       @OA\JsonContent(
     *       type="array",
     *
     *         @OA\Items(ref="#/components/schemas/Page")
     *      )
     *    )
     *  )
     */
    public function index() {}

    /**
     * @OA\Post(
     *     path="/api/pages",
     *     summary="Create a new page",
     *     operationId="createPage",
     *     tags={"Page"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/StorePageRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Page created successfully",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="author_id", type="integer", example=1),
     *             @OA\Property(property="title", type="string", example="Page title"),
     *             @OA\Property(property="content", type="string", example="Page content")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Invalid input data",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="message", type="string", example="Invalid input data")
     *         )
     *     )
     * )
     */
    public function store() {}

    /**
     * @OA\Get(
     *     path="/api/pages/{pageId}",
     *     summary="Get a page by ID",
     *     operationId="getPage",
     *     tags={"Page"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *           name="pageId",
     *           in="path",
     *           description="ID of the page to return",
     *           required=true,
     *
     *            @OA\Schema(type="integer", example=1)
     *    ),
     *
     *     @OA\Response(
     *     response=200,
     *     description="Page details",
     *
     *     @OA\JsonContent(ref="#/components/schemas/Page")
     *    ),
     * )
     */
    public function show() {}

    /**
     * @OA\Put(
     *     path="/api/pages/{pageId}",
     *     summary="Update a page",
     *     operationId="updatePage",
     *     tags={"Page"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="pageId",
     *         in="path",
     *         description="ID of the page to update",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *    ),
     *
     *     @OA\RequestBody(
     *        required=true,
     *
     *        @OA\JsonContent(ref="#/components/schemas/Page")
     *     ),
     *
     *     @OA\Response(
     *        response=200,
     *        description="Page updated successfully"
     *        )
     *     )
     * )
     */
    public function update() {}

    /**
     * @OA\Delete(
     *     path="/api/pages/{pageId}",
     *     summary="Delete a page",
     *     operationId="deletePage",
     *     tags={"Page"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="pageId",
     *         in="path",
     *         description="ID of the page to delete",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Page deleted successfully"
     *     )
     * )
     */
    public function destroy() {}
}
