<?php

declare(strict_types=1);

namespace App\Tests\Shared\Messaging;

use App\Shared\Messaging\DeduplicatingMiddleware;
use App\Shared\Messaging\IdempotencyStamp;
use App\Tests\Support\CountingMiddleware;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\InMemoryStore;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\RecoverableMessageHandlingException;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;

/**
 * "Workers" are separate middleware instances sharing one cache (completion markers) and one
 * lock store (claims) — the same topology as separate processes sharing Redis.
 */
final class DeduplicatingMiddlewareTest extends TestCase
{
    private ArrayAdapter $cache;
    private InMemoryStore $lockStore;

    protected function setUp(): void
    {
        $this->cache = new ArrayAdapter();
        $this->lockStore = new InMemoryStore();
    }

    public function testTheFirstDeliveryIsHandled(): void
    {
        $next = new CountingMiddleware();

        $this->worker()->handle($this->consumedEnvelope(), $this->stackFor($next));

        self::assertSame(1, $next->count);
    }

    public function testASequentialRedeliveryIsNotHandledAgain(): void
    {
        $envelope = $this->consumedEnvelope();
        $next = new CountingMiddleware();

        $this->worker()->handle($envelope, $this->stackFor($next));
        $this->worker()->handle($envelope, $this->stackFor($next));

        self::assertSame(1, $next->count, 'A redelivered message must be handled only once.');
    }

    public function testAConcurrentDuplicateCannotAlsoExecute(): void
    {
        $envelope = $this->consumedEnvelope();
        $workerB = $this->worker();
        $terminalB = new CountingMiddleware();

        // While worker A is inside its handler (claim held), worker B receives the same
        // message: it must not execute and must be deferred for redelivery.
        $conflict = null;
        $terminalA = new CallbackTerminal(function () use ($workerB, $terminalB, $envelope, &$conflict): void {
            try {
                $workerB->handle($envelope, $this->stackFor($terminalB));
            } catch (RecoverableMessageHandlingException $exception) {
                $conflict = $exception;
            }
        });

        $this->worker()->handle($envelope, $this->stackFor($terminalA));

        self::assertInstanceOf(RecoverableMessageHandlingException::class, $conflict);
        self::assertSame(0, $terminalB->count, 'The losing worker must not execute the handler.');

        // The deferred redelivery arrives after the winner completed: suppressed.
        $workerB->handle($envelope, $this->stackFor($terminalB));

        self::assertSame(0, $terminalB->count);
    }

    public function testAFailedHandlingIsNotMarkedCompletedAndStaysRetryable(): void
    {
        $envelope = $this->consumedEnvelope();
        $boom = new CallbackTerminal(static fn () => throw new \RuntimeException('boom'));

        try {
            $this->worker()->handle($envelope, $this->stackFor($boom));
            self::fail('The handler failure must propagate.');
        } catch (\RuntimeException) {
        }

        // The retry is handled: no completion marker was written and the claim was released.
        $next = new CountingMiddleware();

        $this->worker()->handle($envelope, $this->stackFor($next));

        self::assertSame(1, $next->count);
    }

    public function testAStaleClaimDoesNotBlockTheMessageForever(): void
    {
        $envelope = $this->consumedEnvelope('abc');

        // A crashed worker left its claim behind.
        $stale = (new LockFactory($this->lockStore))->createLock('messenger_dedup_claim_abc', 300.0);
        self::assertTrue($stale->acquire());

        $next = new CountingMiddleware();

        try {
            $this->worker()->handle($envelope, $this->stackFor($next));
            self::fail('A held claim must defer the delivery.');
        } catch (RecoverableMessageHandlingException) {
        }

        // In production the Redis store expires the claim after its TTL (verified in dev);
        // the in-memory store never expires, so the release stands in for the expiry.
        $stale->release();

        $this->worker()->handle($envelope, $this->stackFor($next));

        self::assertSame(1, $next->count);
    }

    public function testDifferentMessageIdsAreEachHandled(): void
    {
        $next = new CountingMiddleware();

        $this->worker()->handle($this->consumedEnvelope('one'), $this->stackFor($next));
        $this->worker()->handle($this->consumedEnvelope('two'), $this->stackFor($next));

        self::assertSame(2, $next->count);
    }

    public function testTheDispatchSideIsNeverDeduplicated(): void
    {
        // No ReceivedStamp: this is the dispatch side, which must always pass through.
        $envelope = new Envelope(new \stdClass(), [new IdempotencyStamp('abc')]);
        $next = new CountingMiddleware();

        $this->worker()->handle($envelope, $this->stackFor($next));
        $this->worker()->handle($envelope, $this->stackFor($next));

        self::assertSame(2, $next->count);
    }

    public function testAMessageWithoutIdempotencyStampPassesThrough(): void
    {
        $envelope = new Envelope(new \stdClass(), [new ReceivedStamp('async')]);
        $next = new CountingMiddleware();

        $this->worker()->handle($envelope, $this->stackFor($next));
        $this->worker()->handle($envelope, $this->stackFor($next));

        self::assertSame(2, $next->count);
    }

    private function worker(): DeduplicatingMiddleware
    {
        return new DeduplicatingMiddleware($this->cache, new LockFactory($this->lockStore));
    }

    private function consumedEnvelope(string $id = 'abc'): Envelope
    {
        return new Envelope(new \stdClass(), [new IdempotencyStamp($id), new ReceivedStamp('async')]);
    }

    private function stackFor(MiddlewareInterface $next): StackInterface
    {
        $stack = $this->createStub(StackInterface::class);
        $stack->method('next')->willReturn($next);

        return $stack;
    }
}

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
