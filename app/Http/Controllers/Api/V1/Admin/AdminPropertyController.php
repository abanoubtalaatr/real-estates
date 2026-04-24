<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\ListingType;
use App\Enums\PropertyStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StorePropertyRequest;
use App\Http\Requests\Api\V1\Admin\UpdatePropertyRequest;
use App\Http\Resources\Api\V1\PropertyResource;
use App\Models\Property;
use App\Models\PropertyImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class AdminPropertyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Property::query()->with(['category', 'images', 'assignedAgent.user']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $items = $query->latest()->paginate($request->integer('per_page', 20));

        return PropertyResource::collection($items)->response();
    }

    public function store(StorePropertyRequest $request): JsonResponse
    {
        $data = $request->validated();
        unset($data['images']);

        $data['listing_type'] = ListingType::from($data['listing_type']);
        $data['status'] = PropertyStatus::from($data['status']);

        $property = Property::query()->create($data);
        $this->syncImages($property, $request);
        Cache::forget('api:v1:home');

        $property->load(['category', 'images', 'assignedAgent.user']);

        return response()->json([
            'data' => new PropertyResource($property),
        ], Response::HTTP_CREATED);
    }

    public function show(Property $property): JsonResponse
    {
        $property->load(['category', 'images', 'assignedAgent.user']);

        return response()->json(['data' => new PropertyResource($property)]);
    }

    public function update(UpdatePropertyRequest $request, Property $property): JsonResponse
    {
        $data = $request->validated();
        unset($data['images']);

        if (isset($data['listing_type'])) {
            $data['listing_type'] = ListingType::from($data['listing_type']);
        }

        if (isset($data['status'])) {
            $data['status'] = PropertyStatus::from($data['status']);
        }

        $property->update($data);

        if ($request->hasFile('images')) {
            $property->images()->delete();
            $this->syncImages($property, $request);
        }

        Cache::forget('api:v1:home');

        $property->load(['category', 'images', 'assignedAgent.user']);

        return response()->json(['data' => new PropertyResource($property->fresh())]);
    }

    public function destroy(Property $property): JsonResponse
    {
        $property->delete();
        Cache::forget('api:v1:home');

        return response()->json(['message' => 'Property deleted.']);
    }

    private function syncImages(Property $property, Request $request): void
    {
        if (! $request->hasFile('images')) {
            return;
        }

        foreach ($request->file('images', []) as $index => $file) {
            $path = $file->store('properties/'.$property->id, 'public');
            PropertyImage::query()->create([
                'property_id' => $property->id,
                'path' => $path,
                'sort_order' => $index,
            ]);
        }
    }
}
