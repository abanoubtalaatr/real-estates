<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PropertyResource;
use App\Models\Favorite;
use App\Models\Order;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecommendationController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        $categoryIds = Favorite::query()
            ->where('user_id', $user->id)
            ->with('property')
            ->get()
            ->pluck('property.category_id')
            ->filter()
            ->unique()
            ->values();

        if ($categoryIds->isEmpty()) {
            $categoryIds = Order::query()
                ->where('user_id', $user->id)
                ->with('property')
                ->latest()
                ->limit(20)
                ->get()
                ->pluck('property.category_id')
                ->filter()
                ->unique()
                ->values();
        }

        $query = Property::query()
            ->published()
            ->with(['category', 'images', 'assignedAgent.user']);

        if ($categoryIds->isNotEmpty()) {
            $query->whereIn('category_id', $categoryIds);
        }

        $items = $query->inRandomOrder()->limit(12)->get();

        return response()->json([
            'data' => PropertyResource::collection($items),
        ]);
    }
}
