<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Http\Resources\Api\V1\PropertyResource;
use App\Models\Category;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $payload = Cache::remember('api:v1:home', 60, function (): array {
            $categories = Category::query()->orderBy('sort_order')->orderBy('name')->get();

            $bestSelling = Property::query()
                ->published()
                ->with(['category', 'images', 'assignedAgent.user'])
                ->orderByDesc('sales_count')
                ->limit(8)
                ->get();

            $featured = Property::query()
                ->published()
                ->where('is_featured', true)
                ->with(['category', 'images', 'assignedAgent.user'])
                ->latest()
                ->limit(8)
                ->get();

            $recommended = Property::query()
                ->published()
                ->with(['category', 'images', 'assignedAgent.user'])
                ->inRandomOrder()
                ->limit(8)
                ->get();

            return [
                'categories' => CategoryResource::collection($categories)->resolve(),
                'best_selling' => PropertyResource::collection($bestSelling)->resolve(),
                'featured' => PropertyResource::collection($featured)->resolve(),
                'recommended' => PropertyResource::collection($recommended)->resolve(),
            ];
        });

        return response()->json(['data' => $payload]);
    }
}
