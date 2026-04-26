<?php

namespace App\Notifications;

use App\Models\AppRelease;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TenantUpdateAvailableNotification extends Notification
{
    use Queueable;

    public function __construct(public AppRelease $release) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'category'    => 'update_available',
            'title'       => "Update Available: {$this->release->version_tag}",
            'body'        => $this->release->name
                ? "A new update ({$this->release->name}) is ready to apply to your shop."
                : "A new update ({$this->release->version_tag}) is ready to apply to your shop.",
            'version_tag' => $this->release->version_tag,
            'url'         => route('tenant.updates.index', absolute: false),
        ];
    }

    public function databaseType(object $notifiable): string
    {
        return 'update-available';
    }
}
