<?php

declare(strict_types=1);

namespace App\Notification\Application;

use App\Notification\Domain\Notification;

/**
 * Delivers a notification over each of its declared channels. Adding a new channel is a matter of
 * registering another {@see NotificationChannel} — callers and notifications are unchanged.
 */
final class Notifier
{
    /**
     * @var array<string, NotificationChannel>
     */
    private array $channels = [];

    /**
     * @param iterable<NotificationChannel> $channels
     */
    public function __construct(iterable $channels)
    {
        foreach ($channels as $channel) {
            $this->channels[$channel->name()] = $channel;
        }
    }

    public function send(Notification $notification): void
    {
        foreach ($notification->channels() as $name) {
            $channel = $this->channels[$name]
                ?? throw new \RuntimeException(sprintf('No notification channel named "%s" is registered.', $name));

            $channel->send($notification);
        }
    }
}
