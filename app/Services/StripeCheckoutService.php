<?php

namespace App\Services;

use App\Models\Order;
use Stripe\StripeClient;

class StripeCheckoutService
{
    public function __construct(
        private readonly ?string $secret = null,
    ) {
        //
    }

    public function createSessionForOrder(Order $order): string
    {
        $secret = $this->secret ?? (string) config('services.stripe.secret');

        if ($secret === '') {
            throw new \RuntimeException('Stripe is not configured.');
        }

        $order->loadMissing('property');

        $stripe = new StripeClient($secret);

        $session = $stripe->checkout->sessions->create([
            'mode' => 'payment',
            'success_url' => config('app.frontend_url').'/orders/'.$order->id.'?paid=1',
            'cancel_url' => config('app.frontend_url').'/orders/'.$order->id.'?cancelled=1',
            'metadata' => [
                'order_id' => (string) $order->id,
            ],
            'line_items' => [
                [
                    'quantity' => 1,
                    'price_data' => [
                        'currency' => strtolower((string) $order->currency),
                        'unit_amount' => (int) round((float) $order->amount * 100),
                        'product_data' => [
                            'name' => $order->property?->title ?? 'Property order #'.$order->id,
                        ],
                    ],
                ],
            ],
        ]);

        $order->update([
            'stripe_checkout_session_id' => $session->id,
        ]);

        return (string) $session->url;
    }
}
