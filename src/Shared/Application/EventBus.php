<?php

declare(strict_types=1);

namespace App\Shared\Application;

use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Publishes domain events on the asynchronous event bus (CQRS): fire-and-forget, zero or more
 * handlers. The concrete broker transport is configured in CE-020.
 */
final readonly class EventBus
{
    public function __construct(
        private MessageBusInterface $eventBus,
    ) {
    }

    public function dispatch(object $event): void
    {
        $this->eventBus->dispatch($event);
    }
}
