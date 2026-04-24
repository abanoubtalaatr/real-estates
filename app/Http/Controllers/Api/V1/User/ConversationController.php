<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreConversationRequest;
use App\Http\Resources\Api\V1\ConversationResource;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ConversationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $conversations = Conversation::query()
            ->where(function ($query) use ($user): void {
                $query->where('user_id', $user->id)
                    ->orWhere('agent_user_id', $user->id);
            })
            ->with(['user', 'agentUser', 'property.category', 'property.images'])
            ->latest('updated_at')
            ->paginate($request->integer('per_page', 15));

        return ConversationResource::collection($conversations)->response();
    }

    public function store(StoreConversationRequest $request): JsonResponse
    {
        $agent = User::query()->whereKey($request->validated('agent_user_id'))->firstOrFail();

        if ($agent->role !== UserRole::Agent) {
            return response()->json(['message' => 'Selected user is not an agent.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($agent->id === $request->user()->id) {
            return response()->json(['message' => 'Invalid conversation.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $query = Conversation::query()
            ->where('user_id', $request->user()->id)
            ->where('agent_user_id', $agent->id);

        if ($request->filled('property_id')) {
            $query->where('property_id', $request->validated('property_id'));
        } else {
            $query->whereNull('property_id');
        }

        $conversation = $query->first();

        if (! $conversation) {
            $conversation = Conversation::query()->create([
                'user_id' => $request->user()->id,
                'agent_user_id' => $agent->id,
                'property_id' => $request->validated('property_id'),
            ]);
            $created = true;
        } else {
            $created = false;
        }

        $conversation->load(['user', 'agentUser', 'property.category', 'property.images']);

        return response()->json([
            'data' => new ConversationResource($conversation),
        ], $created ? Response::HTTP_CREATED : Response::HTTP_OK);
    }

    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeConversation($request, $conversation);

        $conversation->load(['user', 'agentUser', 'property.category', 'property.images', 'messages.sender']);

        return response()->json([
            'data' => new ConversationResource($conversation),
        ]);
    }

    private function authorizeConversation(Request $request, Conversation $conversation): void
    {
        $user = $request->user();

        if ($conversation->user_id !== $user->id && $conversation->agent_user_id !== $user->id) {
            abort(Response::HTTP_FORBIDDEN);
        }
    }
}
