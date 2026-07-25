<?php

declare(strict_types=1);

namespace App\Shared\Application;

use App\Shared\Messaging\IdempotencyStamp;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Publishes domain events on the asynchronous event bus (CQRS): fire-and-forget, zero or more
 * handlers. The concrete broker transport is configured in CE-020.
 *
 * Each event is stamped with a unique id so redelivered messages are deduplicated on the
 * consuming side (CE-025, {@see \App\Shared\Messaging\DeduplicatingMiddleware}).
 */
final readonly class EventBus
{
    public function __construct(
        private MessageBusInterface $eventBus,
    ) {
    }

    public function dispatch(object $event): void
    {
        $this->eventBus->dispatch($event, [new IdempotencyStamp(bin2hex(random_bytes(16)))]);
    }
}
