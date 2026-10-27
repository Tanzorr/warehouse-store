<?php

namespace App\Http\Controllers;

use App\Actions\GetNotAccessedEntityUsersAction;
use App\Actions\GetUsersAction;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\SharedAccess;
use App\Models\User;
use App\Queries\GetQuery;
use App\Services\SharedAccessService;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function __construct(
        private readonly SharedAccessService $sharedAccessService,
    ) {}

    public function index(GetUsersAction $getUsersAction): JsonResponse
    {
        return response()->json($getUsersAction->handle(new GetQuery(['search' => request('search')])));
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        return response()->json([
            'message' => 'User created successfully',
            'user' => User::create($request->validated()),
        ]);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json($user);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user->update($request->validated()),
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        return response()->json(null, $user->delete() ? 200 : 404);
    }

    public function getNotAccessedUsers(
        string $entityType,
        string $entityId,
        GetNotAccessedEntityUsersAction $accessedEntityUsersAction
    ): JsonResponse {
        return response()->json($accessedEntityUsersAction->handle(
            new GetQuery([
                'search' => request('search'),
                'accessed_user_ids' => $this->sharedAccessService->getSharedEntityAccessUserIds(
                    SharedAccess::ACCESS_TYPE_MAP[$entityType],
                    $entityId
                )])
        ));
    }
}
