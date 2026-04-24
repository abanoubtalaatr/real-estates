<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\UpdateUserBlockRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = User::query()
            ->latest()
            ->paginate($request->integer('per_page', 30));

        return UserResource::collection($items)->response();
    }

    public function show(User $user): JsonResponse
    {
        return response()->json(['data' => new UserResource($user)]);
    }

    public function updateBlock(UpdateUserBlockRequest $request, User $user): JsonResponse
    {
        if ($request->user()->id === $user->id) {
            return response()->json(['message' => 'You cannot block yourself.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user->is_blocked = $request->validated('is_blocked');
        $user->save();

        if ($user->is_blocked) {
            $user->tokens()->delete();
        }

        return response()->json(['data' => new UserResource($user->fresh())]);
    }
}
