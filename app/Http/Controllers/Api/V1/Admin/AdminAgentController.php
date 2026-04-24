<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreAgentRequest;
use App\Http\Requests\Api\V1\Admin\UpdateAgentRequest;
use App\Http\Resources\Api\V1\AgentResource;
use App\Models\Agent;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAgentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = Agent::query()
            ->with('user')
            ->latest()
            ->paginate($request->integer('per_page', 50));

        return AgentResource::collection($items)->response();
    }

    public function store(StoreAgentRequest $request): JsonResponse
    {
        User::query()->whereKey($request->validated('user_id'))->firstOrFail();

        $agent = Agent::query()->create($request->validated());
        $agent->load('user');

        return response()->json([
            'data' => new AgentResource($agent),
        ], Response::HTTP_CREATED);
    }

    public function show(Agent $agent): JsonResponse
    {
        $agent->load('user');

        return response()->json(['data' => new AgentResource($agent)]);
    }

    public function update(UpdateAgentRequest $request, Agent $agent): JsonResponse
    {
        $payload = $request->validated();

        if (array_key_exists('user_id', $payload) && (int) $payload['user_id'] !== $agent->user_id) {
            $oldUser = $agent->user;
            $oldUser->role = UserRole::User;
            $oldUser->save();

            $newUser = User::query()->whereKey($payload['user_id'])->firstOrFail();
            $newUser->role = UserRole::Agent;
            $newUser->save();
        }

        $agent->update($payload);
        $agent->load('user');

        return response()->json(['data' => new AgentResource($agent->fresh())]);
    }

    public function destroy(Agent $agent): JsonResponse
    {
        $agent->delete();

        return response()->json(['message' => 'Agent removed.']);
    }
}
