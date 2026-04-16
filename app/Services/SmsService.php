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
        $this->baseUrl = config('services.sms.base_url');
        $this->endpoint = config('services.sms.endpoint');
        $this->token = config('services.sms.token');
        $this->connectTimeout = config('services.sms.connect_timeout', 10);
        $this->timeout = config('services.sms.timeout', 45);
        $this->retryTimes = config('services.sms.retry_times', 2);
        $this->retrySleepMs = config('services.sms.retry_sleep_ms', 2000);
    }

    /**
     * Send SMS message.
     */
    public function send(string $phoneNumber, string $message): bool
    {
        if (!$this->token) {
            Log::warning('SMS API token not configured');
            return false;
        }

        // Format phone number (remove spaces, dashes, ensure +63 prefix)
        $phoneNumber = $this->formatPhoneNumber($phoneNumber);

        try {
            // SMS API PH format based on their API structure
            $response = Http::withHeaders([
                    'x-api-key' => $this->token,
                    'Content-Type' => 'application/json',
                ])
                ->timeout($this->timeout)
                ->connectTimeout($this->connectTimeout)
                ->retry($this->retryTimes, $this->retrySleepMs)
                ->post($this->baseUrl . $this->endpoint, [
                    'recipient' => $phoneNumber,
                    'message' => $message,
                ]);

            if ($response->successful()) {
                Log::info('SMS sent successfully', [
                    'phone' => $phoneNumber,
                    'message' => substr($message, 0, 50),
                    'response' => $response->json(),
                ]);
                return true;
            }

            Log::error('SMS sending failed', [
                'phone' => $phoneNumber,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
            return false;

        } catch (\Exception $e) {
            Log::error('SMS sending exception', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Format phone number to international format.
     */
    protected function formatPhoneNumber(string $phoneNumber): string
    {
        // Remove all non-numeric characters
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

        // If starts with 0, replace with +63
        if (str_starts_with($phoneNumber, '0')) {
            $phoneNumber = '+63' . substr($phoneNumber, 1);
        }

        // If doesn't start with +, add +63
        if (!str_starts_with($phoneNumber, '+')) {
            $phoneNumber = '+63' . $phoneNumber;
        }

        return $phoneNumber;
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
        $message = "Hi {$customerName}! Your order #{$orderNumber} is now {$status}. ";
        
        $message .= match($status) {
            'received' => 'We have received your laundry.',
            'processing' => 'Your laundry is being processed.',
            'ready' => 'Your laundry is ready for pickup!',
            'completed' => 'Thank you for choosing our service!',
            default => 'Status updated.',
        };

        return $this->send($phoneNumber, $message);
    }

    /**
     * Send order status update (for existing listener compatibility).
     */
    public function sendOrderStatusUpdate($customer, $order, string $statusLabel): bool
    {
        if (!$customer->phone) {
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
