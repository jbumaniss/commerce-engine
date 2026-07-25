<?php

declare(strict_types=1);

namespace App\Tests\Shared\Messaging;

use App\Shared\Messaging\DeduplicatingMiddleware;
use App\Shared\Messaging\IdempotencyStamp;
use App\Tests\Support\CountingMiddleware;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;

final class DeduplicatingMiddlewareTest extends TestCase
{
    public function testAConsumedMessageIsHandledOnlyOnceAcrossRedeliveries(): void
    {
        $middleware = new DeduplicatingMiddleware(new ArrayAdapter());
        $envelope = new Envelope(new \stdClass(), [new IdempotencyStamp('abc'), new ReceivedStamp('async')]);

        $next = new CountingMiddleware();

        $middleware->handle($envelope, $this->stackFor($next));
        $middleware->handle($envelope, $this->stackFor($next)); // redelivery of the same message

        self::assertSame(1, $next->count, 'A redelivered message must be handled only once.');
    }

    public function testItDoesNotDeduplicateOnDispatch(): void
    {
        $middleware = new DeduplicatingMiddleware(new ArrayAdapter());
        // No ReceivedStamp: the dispatch side must always pass through.
        $envelope = new Envelope(new \stdClass(), [new IdempotencyStamp('abc')]);

        $next = new CountingMiddleware();

        $middleware->handle($envelope, $this->stackFor($next));
        $middleware->handle($envelope, $this->stackFor($next));

        self::assertSame(2, $next->count);
    }

    public function testDifferentMessagesAreEachHandled(): void
    {
        $middleware = new DeduplicatingMiddleware(new ArrayAdapter());
        $next = new CountingMiddleware();

        $middleware->handle(
            new Envelope(new \stdClass(), [new IdempotencyStamp('one'), new ReceivedStamp('async')]),
            $this->stackFor($next),
        );
        $middleware->handle(
            new Envelope(new \stdClass(), [new IdempotencyStamp('two'), new ReceivedStamp('async')]),
            $this->stackFor($next),
        );

        self::assertSame(2, $next->count);
    }

    private function stackFor(CountingMiddleware $next): StackInterface
    {
        $stack = $this->createStub(StackInterface::class);
        $stack->method('next')->willReturn($next);

        return $stack;
    }
}
