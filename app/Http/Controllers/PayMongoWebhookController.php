<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Tenant;
use App\Services\PayMongoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayMongoWebhookController extends Controller
{
    public function __construct(
        protected PayMongoService $paymongo,
    ) {}

    /**
     * Handle a PayMongo webhook event.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Paymongo-Signature', '');

        // Verify signature in production
        if (config('services.paymongo.webhook_secret') && ! $this->paymongo->verifyWebhookSignature($payload, $signature)) {
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        $event = $request->input('data.attributes.type');
        $resource = $request->input('data.attributes.data');

        return match ($event) {
            'checkout_session.payment.paid' => $this->handleCheckoutPaid($resource),
            default => response()->json(['message' => 'Event ignored']),
        };
    }

    /**
     * Handle a successful checkout payment.
     */
    protected function handleCheckoutPaid(array $resource): JsonResponse
    {
        $checkoutId = $resource['id'] ?? null;

        if (! $checkoutId) {
            return response()->json(['error' => 'Missing checkout ID'], 400);
        }

        $payment = Payment::where('paymongo_checkout_id', $checkoutId)->first();

        if (! $payment) {
            return response()->json(['error' => 'Payment not found'], 404);
        }

        if ($payment->isPaid()) {
            return response()->json(['message' => 'Already processed']);
        }

        $paymentMethodType = $resource['attributes']['payment_method_used'] ?? null;
        $paymentIntentId = $resource['attributes']['payments'][0]['id'] ?? null;

        $payment->update([
            'status' => 'paid',
            'payment_method' => $paymentMethodType,
            'paymongo_payment_id' => $paymentIntentId,
            'paid_at' => now(),
        ]);

        // Activate tenant
        $tenant = Tenant::find($payment->tenant_id);

        if ($tenant) {
            $tenant->update([
                'is_paid' => true,
                'is_enabled' => true,
            ]);
        }

        return response()->json(['message' => 'Payment processed']);
    }
}
