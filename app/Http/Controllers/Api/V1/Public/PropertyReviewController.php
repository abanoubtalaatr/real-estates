<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Enums\PropertyStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StorePropertyReviewRequest;
use App\Http\Resources\Api\V1\PropertyReviewResource;
use App\Models\Property;
use App\Models\PropertyReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PropertyReviewController extends Controller
{
    public function index(Request $request, int $property): JsonResponse
    {
        Property::query()->whereKey($property)->where('status', PropertyStatus::Published)->firstOrFail();

        $reviews = PropertyReview::query()
            ->where('property_id', $property)
            ->with('user')
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return PropertyReviewResource::collection($reviews)->response();
    }

    public function store(StorePropertyReviewRequest $request, int $property): JsonResponse
    {
        $model = Property::query()->whereKey($property)->where('status', PropertyStatus::Published)->firstOrFail();

        $review = PropertyReview::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'property_id' => $model->id,
            ],
            [
                'rating' => $request->validated('rating'),
                'comment' => $request->validated('comment'),
            ]
        );

        $review->load('user');

        return response()->json([
            'data' => new PropertyReviewResource($review),
        ], Response::HTTP_CREATED);
    }
}
