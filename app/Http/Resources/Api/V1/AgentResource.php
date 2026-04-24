<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Agent */
class AgentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'bio' => $this->bio,
            'license_number' => $this->license_number,
            'company' => $this->company,
            'user' => $this->whenLoaded('user', fn () => new UserResource($this->user)),
        ];
    }
}
