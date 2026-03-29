<?php

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

test('sms test command sends a provider request', function () {
    config([
        'services.sms.base_url' => 'https://smsapiph.onrender.com/api/v1',
        'services.sms.endpoint' => '/send/sms',
        'services.sms.token' => 'test-sms-key',
    ]);

    Http::fake([
        'https://smsapiph.onrender.com/api/v1/send/sms' => Http::response([
            'success' => true,
        ], 200),
    ]);

    $this->artisan('sms:test', [
        'phone' => '09171234567',
        '--message' => 'Codex SMS test',
    ])
        ->expectsOutput('SMS sent successfully.')
        ->expectsOutput('Recipient: +639171234567')
        ->assertExitCode(0);

    Http::assertSent(function ($request): bool {
        return $request->url() === 'https://smsapiph.onrender.com/api/v1/send/sms'
            && $request->hasHeader('x-api-key', 'test-sms-key')
            && $request['recipient'] === '+639171234567'
            && $request['message'] === 'Codex SMS test';
    });
});

test('sms test command fails for invalid phone numbers', function () {
    config([
        'services.sms.base_url' => 'https://smsapiph.onrender.com/api/v1',
        'services.sms.endpoint' => '/send/sms',
        'services.sms.token' => 'test-sms-key',
    ]);

    Http::fake();

    $this->artisan('sms:test', [
        'phone' => '12345',
    ])
        ->expectsOutput('Invalid Philippine mobile number format.')
        ->assertExitCode(1);

    Http::assertNothingSent();
});
