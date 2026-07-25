<?php

declare(strict_types=1);

namespace App\Notification\Domain;

/**
 * A channel-agnostic notification: it knows who it is for and which channels should deliver it.
 * Channel-specific shapes are expressed by companion interfaces (e.g. {@see EmailNotification}).
 */
interface Notification
{
    public function recipient(): string;

    /**
     * @return list<string> the channel names that should deliver this notification
     */
    public function channels(): array;
}
