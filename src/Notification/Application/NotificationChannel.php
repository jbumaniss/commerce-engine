<?php

declare(strict_types=1);

namespace App\Notification\Application;

use App\Notification\Domain\Notification;

/**
 * A delivery channel (email, SMS, push, …). Channels are registered with the {@see Notifier} and
 * selected by name from a notification's {@see Notification::channels()}.
 */
interface NotificationChannel
{
    public function name(): string;

    public function send(Notification $notification): void;
}
