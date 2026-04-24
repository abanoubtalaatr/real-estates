<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Enums\PropertyStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PropertyResource;
use App\Models\Favorite;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FavoriteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $favorites = Favorite::query()
            ->where('user_id', $request->user()->id)
            ->with(['property.category', 'property.images', 'property.assignedAgent.user'])
            ->latest()
            ->paginate($request->integer('per_page', 15));

        $favorites->setCollection(
            $favorites->getCollection()->map(fn (Favorite $favorite) => $favorite->property)->values()
        );

        return PropertyResource::collection($favorites)->response();
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'property_id' => ['required', 'integer', 'exists:properties,id'],
        ]);

        $property = Property::query()->whereKey($data['property_id'])->firstOrFail();

        if ($property->status !== PropertyStatus::Published) {
            return response()->json(['message' => 'Property is not available.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $favorite = Favorite::query()->firstOrCreate([
            'user_id' => $request->user()->id,
            'property_id' => $property->id,
        ]);

        return response()->json([
            'data' => ['id' => $favorite->id],
        ], $favorite->wasRecentlyCreated ? Response::HTTP_CREATED : Response::HTTP_OK);
    }

    public function destroy(Request $request, int $property): JsonResponse
    {
        Favorite::query()
            ->where('user_id', $request->user()->id)
            ->where('property_id', $property)
            ->delete();

        return response()->json(['message' => 'Removed from favorites.']);
    }
}
