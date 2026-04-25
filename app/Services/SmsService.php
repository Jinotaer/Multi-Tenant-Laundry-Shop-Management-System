<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected string $baseUrl;
    protected string $endpoint;
    protected ?string $token;
    protected int $connectTimeout;
    protected int $timeout;
    protected int $retryTimes;
    protected int $retrySleepMs;

    public function __construct()
    {
        $this->baseUrl = (string) config('services.sms.base_url');
        $this->endpoint = (string) config('services.sms.endpoint');
        $this->token = config('services.sms.token');
        $this->connectTimeout = (int) config('services.sms.connect_timeout', 10);
        $this->timeout = (int) config('services.sms.timeout', 45);
        $this->retryTimes = (int) config('services.sms.retry_times', 2);
        $this->retrySleepMs = (int) config('services.sms.retry_sleep_ms', 2000);
    }

    /**
     * Send an SMS and return a structured result.
     *
     * @return array{success: bool, error: ?string, recipient: ?string, response: ?array}
     */
    public function sendMessage(string $phoneNumber, string $message, array $context = []): array
    {
        $recipient = $this->formatPhoneNumber($phoneNumber);

        if ($recipient === null) {
            Log::warning('SMS recipient rejected: invalid Philippine mobile number', [
                'phone' => $phoneNumber,
                'context' => $context,
            ]);

            return [
                'success' => false,
                'error' => 'Invalid Philippine mobile number format.',
                'recipient' => null,
                'response' => null,
            ];
        }

        if (! $this->token) {
            Log::warning('SMS API token not configured', [
                'recipient' => $recipient,
                'context' => $context,
            ]);

            return [
                'success' => false,
                'error' => 'SMS API token is not configured.',
                'recipient' => $recipient,
                'response' => null,
            ];
        }

        if ($this->baseUrl === '' || $this->endpoint === '') {
            Log::warning('SMS API endpoint not configured', [
                'recipient' => $recipient,
                'context' => $context,
            ]);

            return [
                'success' => false,
                'error' => 'SMS API endpoint is not configured.',
                'recipient' => $recipient,
                'response' => null,
            ];
        }

        try {
            $response = Http::withHeaders([
                    'x-api-key' => $this->token,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->timeout($this->timeout)
                ->connectTimeout($this->connectTimeout)
                ->retry($this->retryTimes, $this->retrySleepMs, throw: false)
                ->post(rtrim($this->baseUrl, '/').'/'.ltrim($this->endpoint, '/'), [
                    'recipient' => $recipient,
                    'message' => $message,
                ]);

            $payload = $this->decodeResponse($response->body());

            if ($response->successful()) {
                Log::info('SMS sent successfully', [
                    'recipient' => $recipient,
                    'message_preview' => substr($message, 0, 80),
                    'context' => $context,
                ]);

                return [
                    'success' => true,
                    'error' => null,
                    'recipient' => $recipient,
                    'response' => $payload,
                ];
            }

            $error = is_array($payload) && isset($payload['error']) && is_string($payload['error'])
                ? $payload['error']
                : 'SMS provider returned HTTP '.$response->status().'.';

            Log::error('SMS sending failed', [
                'recipient' => $recipient,
                'status' => $response->status(),
                'response' => $response->body(),
                'context' => $context,
            ]);

            return [
                'success' => false,
                'error' => $error,
                'recipient' => $recipient,
                'response' => $payload,
            ];

        } catch (\Throwable $e) {
            Log::error('SMS sending exception', [
                'recipient' => $recipient,
                'error' => $e->getMessage(),
                'context' => $context,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'recipient' => $recipient,
                'response' => null,
            ];
        }
    }

    /**
     * Backwards-compatible boolean send (used by legacy callers).
     */
    public function send(string $phoneNumber, string $message): bool
    {
        return $this->sendMessage($phoneNumber, $message)['success'];
    }

    /**
     * Validate + normalize a Philippine mobile number to +639XXXXXXXXX.
     * Returns null when the number is not a valid PH mobile format.
     */
    protected function formatPhoneNumber(string $phoneNumber): ?string
    {
        $digits = preg_replace('/\D+/', '', $phoneNumber) ?? '';

        if ($digits === '') {
            return null;
        }

        // Strip country code / trunk prefix so we're left with the 10-digit subscriber number.
        if (str_starts_with($digits, '63')) {
            $subscriber = substr($digits, 2);
        } elseif (str_starts_with($digits, '0')) {
            $subscriber = substr($digits, 1);
        } else {
            $subscriber = $digits;
        }

        // PH mobile numbers are 10 digits and always start with 9.
        if (! preg_match('/^9\d{9}$/', $subscriber)) {
            return null;
        }

        return '+63'.$subscriber;
    }

    protected function decodeResponse(string $body): ?array
    {
        if ($body === '') {
            return null;
        }

        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Send order status notification SMS.
     */
    public function sendOrderStatusNotification(
        string $phoneNumber,
        string $customerName,
        string $orderNumber,
        string $status
    ): bool {
        $message = "LaundryTrack: Order {$orderNumber} is now {$status}.";

        return $this->sendMessage($phoneNumber, $message, [
            'customer' => $customerName,
            'order_number' => $orderNumber,
            'status' => $status,
        ])['success'];
    }

    /**
     * Send order status update (for existing listener compatibility).
     */
    public function sendOrderStatusUpdate($customer, $order, string $statusLabel): bool
    {
        if (! $customer || empty($customer->phone)) {
            return false;
        }

        return $this->sendOrderStatusNotification(
            $customer->phone,
            $customer->name,
            $order->order_number,
            $statusLabel
        );
    }
}
