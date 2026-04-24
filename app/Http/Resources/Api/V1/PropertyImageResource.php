<?php

namespace App\Http\Resources\Api\V1;

use App\Models\PropertyImage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin PropertyImage */
class PropertyImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $path = $this->path;

        return [
            'id' => $this->id,
            'url' => str_starts_with($path, 'http') ? $path : Storage::disk('public')->url($path),
            'sort_order' => $this->sort_order,
        ];
    }
}
