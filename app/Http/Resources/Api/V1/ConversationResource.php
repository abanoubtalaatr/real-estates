<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Conversation */
class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => new UserResource($this->whenLoaded('user')),
            'agent_user' => new UserResource($this->whenLoaded('agentUser')),
            'property' => new PropertyResource($this->whenLoaded('property')),
            'messages' => MessageResource::collection($this->whenLoaded('messages')),
            'updated_at' => $this->updated_at,
        ];
    }
}
