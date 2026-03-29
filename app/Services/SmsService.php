<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SmsService
{
    /**
     * Send a direct SMS message using the configured gateway.
     *
     * @param  array<string, mixed>  $context
     * @return array{success: bool, recipient: string|null, response: mixed, error: string|null}
     */
    public function sendMessage(string $phone, string $message, array $context = []): array
    {
        $recipient = $this->normalizePhilippinePhone($phone);

        if ($recipient === null) {
            return [
                'success' => false,
                'recipient' => null,
                'response' => null,
                'error' => 'Invalid Philippine mobile number format.',
            ];
        }

        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'recipient' => $recipient,
                'response' => null,
                'error' => 'SMS API is not configured.',
            ];
        }

        $baseUrl = rtrim((string) config('services.sms.base_url'), '/');
        $endpoint = (string) config('services.sms.endpoint', '/send/sms');
        $payload = [
            'recipient' => $recipient,
            'message' => $message,
        ];

        try {
            $response = Http::acceptJson()
                ->connectTimeout((int) config('services.sms.connect_timeout', 10))
                ->timeout((int) config('services.sms.timeout', 45))
                ->retry(
                    (int) config('services.sms.retry_times', 2),
                    (int) config('services.sms.retry_sleep_ms', 2000),
                )
                ->withHeaders([
                    'x-api-key' => (string) config('services.sms.token'),
                ])
                ->post("{$baseUrl}{$endpoint}", $payload);

            $response->throw();

            return [
                'success' => true,
                'recipient' => $recipient,
                'response' => $response->json() ?? $response->body(),
                'error' => null,
            ];
        } catch (\Throwable $exception) {
            Log::warning('SMS delivery failed.', array_merge($context, [
                'phone' => $phone,
                'normalized_phone' => $recipient,
                'message' => $exception->getMessage(),
            ]));

            return [
                'success' => false,
                'recipient' => $recipient,
                'response' => null,
                'error' => $exception->getMessage(),
            ];
        }
    }

    /**
     * Send an order update SMS using the configured gateway.
     */
    /**
     * Send an order update SMS using the configured gateway.
     *
     * @return array{success: bool, recipient: string|null, response: mixed, error: string|null}
     */
    public function sendOrderStatusUpdate(Customer $customer, Order $order, string $statusLabel): array
    {
        return $this->sendMessage(
            (string) $customer->phone,
            "LaundryTrack: Order {$order->order_number} is now {$statusLabel}.",
            [
                'customer_id' => $customer->id,
                'order_id' => $order->id,
            ],
        );
    }

    /**
     * Determine whether the SMS gateway is configured.
     */
    public function isConfigured(): bool
    {
        return filled(config('services.sms.base_url'))
            && filled(config('services.sms.token'));
    }

    private function normalizePhilippinePhone(?string $phone): ?string
    {
        if (! filled($phone)) {
            return null;
        }

        $value = preg_replace('/[^\d+]/', '', (string) $phone) ?? '';

        if (Str::startsWith($value, '+639') && strlen($value) === 13) {
            return $value;
        }

        if (Str::startsWith($value, '639') && strlen($value) === 12) {
            return '+'.$value;
        }

        if (Str::startsWith($value, '09') && strlen($value) === 11) {
            return '+63'.substr($value, 1);
        }

        if (Str::startsWith($value, '9') && strlen($value) === 10) {
            return '+63'.$value;
        }

        return null;
    }
}
