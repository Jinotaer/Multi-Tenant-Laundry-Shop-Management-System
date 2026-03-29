<?php

namespace App\Console\Commands;

use App\Services\SmsService;
use Illuminate\Console\Command;

class SmsTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:test
                            {phone : The Philippine mobile number to receive the test message}
                            {--message= : Optional custom SMS body}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test SMS using the configured SMS API provider';

    /**
     * Execute the console command.
     */
    public function handle(SmsService $smsService): int
    {
        $message = $this->option('message');
        $message = is_string($message) && $message !== ''
            ? $message
            : 'LaundryTrack test SMS sent at '.now()->format('Y-m-d h:i A');

        $result = $smsService->sendMessage(
            (string) $this->argument('phone'),
            $message,
            ['source' => 'artisan sms:test'],
        );

        if (! $result['success']) {
            $this->error($result['error'] ?? 'SMS send failed.');

            if ($result['recipient']) {
                $this->line('Normalized recipient: '.$result['recipient']);
            }

            return self::FAILURE;
        }

        $this->info('SMS sent successfully.');
        $this->line('Recipient: '.$result['recipient']);

        if ($result['response'] !== null) {
            $this->line('Provider response: '.json_encode($result['response']));
        }

        return self::SUCCESS;
    }
}
