<?php

namespace App\Http\Controllers\Swagger;

class AuthController
{
    /**
     * @OA\Post(
     *     path="/api/login",
     *     tags={"Auth"},
     *     summary="Log in a user",
     *     operationId="login",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/LoginUserRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="User logged in successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="User logged in successfully"),
     *             @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJzdWIiOiIxIn0")
     *         )
     *     )
     * )
     */
    public function login() {}

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     tags={"Auth"},
     *     summary="Log out a user",
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="User logged out successfully"
     *     )
     * )
     */
    public function logout() {}
}
