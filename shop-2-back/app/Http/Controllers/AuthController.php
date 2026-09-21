<?php

namespace App\Http\Controllers;

use App\Actions\LoginAction;
use App\Http\Requests\LoginUserRequest;
use App\Queries\GetQuery;
use Illuminate\Http\JsonResponse;
use Session;

class AuthController extends Controller
{
    public function login(LoginUserRequest $request, LoginAction $loginAction): JsonResponse
    {
        return response()->json($loginAction->handle(new GetQuery(['credentials' => $request->validated()])), 200);
    }

    /**
     * Log out the authenticated user.
     */
    public function logout(): JsonResponse
    {
        Session::get('user')?->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully'], 200);
    }
}
