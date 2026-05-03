<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Property */
class PropertyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price,
            'listing_type' => $this->listing_type->value,
            'status' => $this->status->value,
            'bedrooms' => $this->bedrooms,
            'bathrooms' => $this->bathrooms,
            'kitchens' => $this->kitchens,
            'is_featured' => $this->is_featured,
            'sales_count' => $this->sales_count,
            'rate' => $this->rate !== null ? round((float) $this->rate, 2) : null,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'lat' => $this->latitude,
            'lng' => $this->longitude,
            'address' => $this->address,
            'distance_km' => $this->distance_km !== null ? round((float) $this->distance_km, 3) : null,
            'distance' => $this->distance_km !== null ? round((float) $this->distance_km, 3) : null,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'images' => PropertyImageResource::collection($this->whenLoaded('images')),
            'agent' => new AgentResource($this->whenLoaded('assignedAgent')),
        ];
    }
}
