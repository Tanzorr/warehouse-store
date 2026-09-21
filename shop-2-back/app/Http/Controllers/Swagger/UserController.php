<?php

namespace App\Http\Controllers\Swagger;

class UserController
{
    /**
     * @OA\Get(
     *     path="/api/users",
     *     tags={"Users"},
     *     summary="Get a list of users",
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search term",
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of users",
     *
     *         @OA\JsonContent(
     *             type="array",
     *
     *             @OA\Items(ref="#/components/schemas/User")
     *         )
     *     )
     * )
     */
    public function index() {}

    /**
     * @OA\Post(
     *     path="/api/users",
     *     tags={"Users"},
     *     summary="Create a new user",
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/StoreUserRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="User created successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="User created successfully"),
     *             @OA\Property(property="user", ref="#/components/schemas/User")
     *         )
     *     )
     * )
     */
    public function store() {}

    /**
     * @OA\Get(
     *     path="/api/users/{user}",
     *     tags={"Users"},
     *     summary="Get details of a specific user",
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="User details",
     *
     *         @OA\JsonContent(ref="#/components/schemas/User")
     *     )
     * )
     */
    public function show() {}

    /**
     * @OA\Put(
     *     path="/api/users/{user}",
     *     tags={"Users"},
     *     summary="Update a user",
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/User")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="User updated successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="User updated successfully"),
     *             @OA\Property(property="user", ref="#/components/schemas/User")
     *         )
     *     )
     * )
     */
    public function update() {}

    /**
     * @OA\Delete(
     *     path="/api/users/{user}",
     *     tags={"Users"},
     *     summary="Delete a user",
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="User deleted successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="User deleted successfully")
     *         )
     *     )
     * )
     */
    public function destroy() {}

    /**
     * @OA\Get(
     *     path="/api/users/not-accessed/{entityType}/{entityId}",
     *     tags={"Users"},
     *     summary="Get users who do not have access to a specific entity",
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="entityType",
     *         in="path",
     *         required=true,
     *         description="Entity type",
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         name="entityId",
     *         in="path",
     *         required=true,
     *         description="Entity ID",
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of users",
     *
     *         @OA\JsonContent(
     *             type="array",
     *
     *             @OA\Items(ref="#/components/schemas/User")
     *         )
     *     )
     * )
     */
    public function getNotAccessedUsers() {}
}
