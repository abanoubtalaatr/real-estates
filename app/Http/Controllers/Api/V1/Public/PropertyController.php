<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PropertyResource;
use App\Models\Property;
use App\Support\Geo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'listing_type' => ['sometimes', 'string', 'in:sale,rent'],
            'search' => ['sometimes', 'string', 'max:255'],
            'latitude' => ['sometimes', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'numeric', 'between:-180,180'],
            'radius_km' => ['sometimes', 'numeric', 'between:1,200'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $query = Property::query()
            ->published()
            ->with(['category', 'images', 'assignedAgent.user']);

        if ($request->filled('category_id')) {
            $query->where('category_id', (int) $request->input('category_id'));
        }

        if ($request->filled('listing_type')) {
            $query->where('listing_type', $request->input('listing_type'));
        }

        if ($request->filled('search')) {
            $search = (string) $request->input('search');
            $query->where('title', 'like', '%'.$search.'%');
        }

        $lat = $request->input('latitude');
        $lng = $request->input('longitude');

        if ($request->filled(['latitude', 'longitude'])) {
            $radiusKm = (float) $request->input('radius_km', 50);
            $lat = (float) $lat;
            $lng = (float) $lng;
            $latPad = $radiusKm / 111.0;
            $cos = cos(deg2rad($lat));
            $lngPad = abs($cos) > 0.01 ? $radiusKm / (111.0 * $cos) : $radiusKm / 111.0;

            $query->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->whereBetween('latitude', [$lat - $latPad, $lat + $latPad])
                ->whereBetween('longitude', [$lng - $lngPad, $lng + $lngPad]);

            $perPage = $request->integer('per_page', 15);
            $page = $request->integer('page', 1);

            $candidates = $query->limit(200)->get()->map(function (Property $property) use ($lat, $lng) {
                $property->setAttribute(
                    'distance_km',
                    Geo::haversineKm($lat, $lng, (float) $property->latitude, (float) $property->longitude)
                );

                return $property;
            })->sortBy('distance_km')->values();

            $total = $candidates->count();
            $slice = $candidates->forPage($page, $perPage)->values();

            return response()->json([
                'data' => PropertyResource::collection($slice),
                'meta' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => (int) max(1, (int) ceil($total / $perPage)),
                ],
            ]);
        }

        $paginator = $query->latest()->paginate(
            $request->integer('per_page', 15),
            ['*'],
            'page',
            $request->integer('page', 1)
        );

        return PropertyResource::collection($paginator)->response();
    }

    public function show(string $property): JsonResponse
    {
        $model = Property::query()
            ->published()
            ->with(['category', 'images', 'assignedAgent.user'])
            ->where(function ($query) use ($property): void {
                $query->where('slug', $property);
                if (ctype_digit((string) $property)) {
                    $query->orWhere('id', (int) $property);
                }
            })
            ->firstOrFail();

        return response()->json(['data' => new PropertyResource($model)]);
    }

    public function similar(int $property): JsonResponse
    {
        $model = Property::query()
            ->published()
            ->whereKey($property)
            ->firstOrFail();

        $related = Property::query()
            ->published()
            ->where('id', '!=', $model->id)
            ->where('category_id', $model->category_id)
            ->with(['category', 'images', 'assignedAgent.user'])
            ->limit(8)
            ->get();

        return response()->json([
            'data' => PropertyResource::collection($related),
        ]);
    }
}
