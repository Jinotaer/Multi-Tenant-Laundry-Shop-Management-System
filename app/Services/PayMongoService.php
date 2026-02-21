<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PayMongoService
{
    protected string $baseUrl;

    protected string $secretKey;

    public function __construct()
    {
        $this->baseUrl = config('services.paymongo.base_url');
        $this->secretKey = config('services.paymongo.secret_key');
    }

    /**
     * Create a PayMongo Checkout Session.
     *
     * @param  array{amount: int, description: string, currency: string, success_url: string, cancel_url: string, metadata: array}  $params
     * @return array{id: string, checkout_url: string}
     */
    public function createCheckoutSession(array $params): array
    {
        $response = Http::withBasicAuth($this->secretKey, '')
            ->post("{$this->baseUrl}/checkout_sessions", [
                'data' => [
                    'attributes' => [
                        'line_items' => [
                            [
                                'name' => $params['description'] ?? 'Subscription Payment',
                                'amount' => $params['amount'], // in centavos
                                'currency' => $params['currency'] ?? 'PHP',
                                'quantity' => 1,
                            ],
                        ],
                        'payment_method_types' => [
                            'gcash',
                            'grab_pay',
                            'card',
                            'paymaya',
                        ],
                        'success_url' => $params['success_url'],
                        'cancel_url' => $params['cancel_url'],
                        'description' => $params['description'] ?? 'Subscription Payment',
                        'metadata' => $params['metadata'] ?? [],
                    ],
                ],
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException(
                'PayMongo checkout creation failed: '.$response->body()
            );
        }

        $data = $response->json('data');

        return [
            'id' => $data['id'],
            'checkout_url' => $data['attributes']['checkout_url'],
        ];
    }

    /**
     * Retrieve a Checkout Session by ID.
     *
     * @return array<string, mixed>
     */
    public function getCheckoutSession(string $checkoutId): array
    {
        $response = Http::withBasicAuth($this->secretKey, '')
            ->get("{$this->baseUrl}/checkout_sessions/{$checkoutId}");

        if (! $response->successful()) {
            throw new \RuntimeException(
                'PayMongo checkout retrieval failed: '.$response->body()
            );
        }

        return $response->json('data');
    }

    /**
     * Check the payment status of a checkout session.
     *
     * @return array{status: string|null, checkout_url: string|null, link_status: string|null, payment_method: string|null, payment_id: string|null}
     */
    public function getCheckoutSessionStatus(string $checkoutId): array
    {
        $data = $this->getCheckoutSession($checkoutId);

        $attributes = $data['attributes'] ?? [];

        return [
            'status' => $attributes['payment_intent']['attributes']['status'] ?? null,
            'checkout_url' => $attributes['checkout_url'] ?? null,
            'link_status' => $attributes['status'] ?? null,
            'payment_method' => $attributes['payment_method_used'] ?? null,
            'payment_id' => $attributes['payments'][0]['id'] ?? null,
        ];
    }

    /**
     * Check if a checkout session has been successfully paid.
     */
    public function isCheckoutPaid(string $checkoutId): bool
    {
        try {
            $status = $this->getCheckoutSessionStatus($checkoutId);

            return $status['status'] === 'succeeded'
                || $status['link_status'] === 'paid';
        } catch (\RuntimeException) {
            return false;
        }
    }

    /**
     * Verify a webhook signature.
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $webhookSecret = config('services.paymongo.webhook_secret');

        if (! $webhookSecret || ! $signature) {
            return false;
        }

        // PayMongo sends signatures in the format: t=timestamp,te=test_signature,li=live_signature
        $parts = collect(explode(',', $signature))
            ->mapWithKeys(function ($part) {
                $segments = explode('=', $part, 2);

                if (count($segments) !== 2) {
                    return [];
                }

                return [$segments[0] => $segments[1]];
            });

        $timestamp = $parts->get('t');
        $testSignature = $parts->get('te');
        $liveSignature = $parts->get('li');

        if (! $timestamp) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', "{$timestamp}.{$payload}", $webhookSecret);

        // Check test signature in test mode, live signature in live mode
        $signatureToCompare = str_starts_with($this->secretKey, 'sk_test_')
            ? $testSignature
            : $liveSignature;

        return hash_equals($expectedSignature, $signatureToCompare ?? '');
    }
}
