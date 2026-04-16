<?php

namespace Database\Seeders;

use App\Models\CannedResponse;
use Illuminate\Database\Seeder;

class CannedResponseSeeder extends Seeder
{
    public function run(): void
    {
        $responses = [
            [
                'title' => 'Welcome & Thank You',
                'shortcut' => '/welcome',
                'content' => 'Thank you for contacting support! We have received your request and will respond as soon as possible. Our team is here to help you.',
                'category' => 'greeting',
            ],
            [
                'title' => 'Issue Resolved',
                'shortcut' => '/resolved',
                'content' => 'Great news! Your issue has been resolved. Please let us know if you need any further assistance or if the problem persists.',
                'category' => 'closing',
            ],
            [
                'title' => 'Need More Information',
                'shortcut' => '/moreinfo',
                'content' => 'To better assist you, could you please provide more details about the issue? Screenshots or error messages would be very helpful.',
                'category' => 'follow-up',
            ],
            [
                'title' => 'Payment Issue - Investigating',
                'shortcut' => '/payment',
                'content' => 'We are currently investigating the payment issue you reported. Our team is working on this and will update you within 24 hours.',
                'category' => 'billing',
            ],
            [
                'title' => 'Technical Issue - Escalated',
                'shortcut' => '/escalate',
                'content' => 'Your technical issue has been escalated to our development team. We will keep you updated on the progress and resolution.',
                'category' => 'technical',
            ],
            [
                'title' => 'Feature Request Received',
                'shortcut' => '/feature',
                'content' => 'Thank you for your feature request! We have added it to our product roadmap and will consider it for future updates.',
                'category' => 'feature',
            ],
            [
                'title' => 'Account Access Help',
                'shortcut' => '/access',
                'content' => 'I can help you with account access. Please verify your email address and I will send you a password reset link.',
                'category' => 'account',
            ],
            [
                'title' => 'Closing Ticket',
                'shortcut' => '/close',
                'content' => 'I am closing this ticket as resolved. If you need further assistance, feel free to reopen this ticket or create a new one. Thank you!',
                'category' => 'closing',
            ],
        ];

        foreach ($responses as $response) {
            CannedResponse::create($response);
        }
    }
}
