<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreMessageRequest;
use App\Http\Resources\Api\V1\MessageResource;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class MessageController extends Controller
{
    public function store(StoreMessageRequest $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        if ($conversation->user_id !== $user->id && $conversation->agent_user_id !== $user->id) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $message = Message::query()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'body' => $request->validated('body'),
        ]);

        $conversation->touch();

        $message->load('sender');

        return response()->json([
            'data' => new MessageResource($message),
        ], Response::HTTP_CREATED);
    }
}
