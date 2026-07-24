<?php

declare(strict_types=1);

namespace App\Shared\Application;

use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Synchronous command bus: dispatches a command to its single handler in-process and returns
 * the handler's result.
 *
 * Messenger wraps a handler exception in a HandlerFailedException; this unwraps it so the
 * original application exception reaches the RFC 7807 exception listener unchanged (preserving
 * the existing #[WithHttpStatus] mapping, e.g. 409 for a slug/version conflict).
 */
final class CommandBus
{
    use HandleTrait {
        handle as private handleMessage;
    }

    public function __construct(MessageBusInterface $commandBus)
    {
        $this->messageBus = $commandBus;
    }

    public function handle(object $command): mixed
    {
        try {
            return $this->handleMessage($command);
        } catch (HandlerFailedException $exception) {
            throw $exception->getPrevious() ?? $exception;
        }
    }
}
