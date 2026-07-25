<?php

declare(strict_types=1);

namespace App\Tests\Support;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

/**
 * Test double: a terminal middleware running a callback, used to interleave a competing
 * consumer inside an in-flight handling and to simulate handler failures.
 */
final readonly class CallbackTerminal implements MiddlewareInterface
{
    public function __construct(private \Closure $callback)
    {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        ($this->callback)();

        return $envelope;
    }
}
