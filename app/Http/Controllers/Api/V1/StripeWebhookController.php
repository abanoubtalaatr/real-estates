<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $secret = (string) config('services.stripe.webhook_secret');

        if ($secret === '') {
            return response('Webhook not configured.', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        Stripe::setApiKey((string) config('services.stripe.secret'));

        $payload = $request->getContent();
        $sigHeader = (string) $request->header('Stripe-Signature', '');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (SignatureVerificationException|\UnexpectedValueException) {
            return response('Invalid signature.', Response::HTTP_BAD_REQUEST);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $orderId = $session->metadata->order_id ?? null;

            if ($orderId) {
                $order = Order::query()->whereKey($orderId)->first();

                if ($order && $order->status === OrderStatus::Pending) {
                    $order->update([
                        'status' => OrderStatus::Paid,
                        'stripe_payment_intent_id' => $session->payment_intent ?? null,
                    ]);

                    $order->property?->increment('sales_count');
                }
            }
        }

        return response('OK', Response::HTTP_OK);
    }
}
