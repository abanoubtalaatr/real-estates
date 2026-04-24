<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Enums\OrderStatus;
use App\Enums\PropertyStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreOrderRequest;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use App\Models\Property;
use App\Services\StripeCheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->where('user_id', $request->user()->id)
            ->with(['property.category', 'property.images', 'property.assignedAgent.user'])
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return OrderResource::collection($orders)->response();
    }

    public function store(StoreOrderRequest $request, StripeCheckoutService $stripe): JsonResponse
    {
        $property = Property::query()->whereKey($request->validated('property_id'))->firstOrFail();

        if ($property->status !== PropertyStatus::Published) {
            return response()->json(['message' => 'Property is not available for purchase.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $pendingExists = Order::query()
            ->where('user_id', $request->user()->id)
            ->where('property_id', $property->id)
            ->where('status', OrderStatus::Pending)
            ->exists();

        if ($pendingExists) {
            return response()->json(['message' => 'You already have a pending order for this property.'], Response::HTTP_CONFLICT);
        }

        $order = Order::query()->create([
            'user_id' => $request->user()->id,
            'property_id' => $property->id,
            'amount' => $property->price,
            'currency' => 'usd',
            'status' => OrderStatus::Pending,
        ]);

        $paymentUrl = null;

        try {
            $paymentUrl = $stripe->createSessionForOrder($order);
        } catch (\Throwable) {
            // Leave pending without checkout until Stripe is configured.
        }

        $order->load(['property.category', 'property.images', 'property.assignedAgent.user']);

        return response()->json([
            'data' => [
                'order' => new OrderResource($order),
                'payment_url' => $paymentUrl,
            ],
        ], Response::HTTP_CREATED);
    }
}
