<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Property;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $paidTotal = Order::query()->where('status', OrderStatus::Paid)->sum('amount');

        return [
            Stat::make('Users', (string) User::query()->count()),
            Stat::make('Properties', (string) Property::query()->count()),
            Stat::make('Paid revenue', '$'.number_format((float) $paidTotal, 2)),
        ];
    }
}
