<?php

namespace App\Console\Commands;

use App\Support\Notifications\WebPushNotificationService;
use Illuminate\Console\Command;

class SendWebPushNotifications extends Command
{
    protected $signature = 'notifications:push';

    protected $description = 'Send pending browser Web Push notifications.';

    public function handle(WebPushNotificationService $webPush): int
    {
        $result = $webPush->sendPending();

        $this->info(sprintf(
            'Web push checked=%d sent=%d failed=%d expired=%d',
            $result['checked'] ?? 0,
            $result['sent'] ?? 0,
            $result['failed'] ?? 0,
            $result['expired'] ?? 0
        ));

        return self::SUCCESS;
    }
}
