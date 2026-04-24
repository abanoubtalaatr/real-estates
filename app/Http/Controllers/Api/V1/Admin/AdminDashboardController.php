<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Property;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => [
                'users_count' => User::query()->count(),
                'properties_count' => Property::query()->count(),
                'paid_revenue' => Order::query()->where('status', OrderStatus::Paid)->sum('amount'),
                'orders_pending' => Order::query()->where('status', OrderStatus::Pending)->count(),
                'orders_paid' => Order::query()->where('status', OrderStatus::Paid)->count(),
            ],
        ]);
    }
}
