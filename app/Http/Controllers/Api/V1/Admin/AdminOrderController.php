<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Order::query()->with(['user', 'property.category', 'property.images', 'property.assignedAgent.user']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $items = $query->latest()->paginate($request->integer('per_page', 30));

        return OrderResource::collection($items)->response();
    }

    public function show(Order $order): JsonResponse
    {
        $order->load(['user', 'property.category', 'property.images', 'property.assignedAgent.user']);

        return response()->json(['data' => new OrderResource($order)]);
    }
}
