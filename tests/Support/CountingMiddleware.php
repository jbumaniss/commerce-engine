<?php

declare(strict_types=1);

namespace App\Tests\Support;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

/**
 * Test double: the terminal middleware in a stack, counting how many times it is reached.
 */
final class CountingMiddleware implements MiddlewareInterface
{
    public int $count = 0;

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        ++$this->count;

        return $envelope;
    }
}
