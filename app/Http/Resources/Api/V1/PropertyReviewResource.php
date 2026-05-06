<?php

namespace App\Http\Resources\Api\V1;

use App\Models\PropertyReview;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PropertyReview */
class PropertyReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'comment' => $this->whenNotNull($this->comment),
            'created_at' => $this->created_at,
            'user' => $this->whenLoaded('user', function () use ($request): array {
                $user = (new UserResource($this->resource->user))->toArray($request);
                $user['image'] = $this->userImageUrl();

                return $user;
            }),
        ];
    }

    private function userImageUrl(): string
    {
        $email = strtolower(trim((string) $this->resource->user?->email));
        $hash = md5($email);

        return "https://www.gravatar.com/avatar/{$hash}?d=identicon&s=200";
    }
}
