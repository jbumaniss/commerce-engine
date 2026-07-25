<?php

declare(strict_types=1);

namespace App\Tests\Shared\Messaging;

use App\Shared\Messaging\DeduplicatingMiddleware;
use App\Shared\Messaging\IdempotencyStamp;
use App\Tests\Support\CallbackTerminal;
use App\Tests\Support\CountingMiddleware;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\InMemoryStore;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\EventListener\SendFailedMessageForRetryListener;
use Symfony\Component\Messenger\Exception\RecoverableMessageHandlingException;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Messenger\Stamp\TransportMessageIdStamp;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

/**
 * Proves the losing side of a claim race is retried with the middleware's own, fixed
 * CONTENTION_RETRY_DELAY_MS — not the async transport's business retry_strategy (CE-022) — by
 * running the real exception through the real framework retry pipeline (the same
 * SendFailedMessageForRetryListener and "async" transport the production worker uses).
 *
 * This guards against both directions of coupling: a future change to the transport's
 * retry_strategy (delay/multiplier/max_delay) must not silently change contention backoff, and
 * vice versa.
 */
final class ContentionRetryDelayTest extends KernelTestCase
{
    public function testTheLosingSideOfAClaimRaceIsRetriedWithTheFixedContentionDelay(): void
    {
        $conflict = $this->captureContentionException();

        $listener = self::getContainer()->get('messenger.retry.send_failed_message_for_retry_listener');
        self::assertInstanceOf(SendFailedMessageForRetryListener::class, $listener);

        $transport = self::getContainer()->get('messenger.transport.async');
        self::assertInstanceOf(InMemoryTransport::class, $transport);

        $envelope = new Envelope(new \stdClass(), [new TransportMessageIdStamp(1)]);
        $event = new WorkerMessageFailedEvent($envelope, 'async', $conflict);

        $listener->onMessageFailed($event);

        // Contention must remain retryable even though it bypasses the transport's max_retries
        // (RecoverableMessageHandlingException::forceRetry defaults to true).
        self::assertTrue($event->willRetry());

        $retried = $transport->getSent();

        self::assertNotEmpty($retried, 'The retry listener must have re-sent the message.');

        $delay = $retried[array_key_last($retried)]->last(DelayStamp::class);

        self::assertNotNull($delay, 'The redelivery must carry an explicit delay, not fire immediately.');

        // Exactly 5000ms: no jitter and no fallback to the async transport's own retry_strategy
        // (configured at delay:1000/multiplier:2 — a materially different value), proving the
        // delay is the middleware's own and not inherited from unrelated transport config.
        self::assertSame(5000, $delay->getDelay());
    }

    public function testTheWorstCaseRetryCountForAStuckClaimStaysBounded(): void
    {
        // Regression guard: if CLAIM_TTL or CONTENTION_RETRY_DELAY_MS is ever changed without
        // considering the other, this catches a reintroduced excessive retry count (e.g. the
        // original 500ms delay against a 300s TTL produced 600 worst-case retries — a message
        // stuck behind a crashed worker's claim would be retried roughly twice a second for up
        // to five minutes). The bound below (100) is generous headroom over the current design
        // value (60), not itself the target.
        $middleware = new \ReflectionClass(DeduplicatingMiddleware::class);
        $claimTtlSeconds = $middleware->getConstant('CLAIM_TTL');
        $delayMs = $middleware->getConstant('CONTENTION_RETRY_DELAY_MS');

        self::assertIsFloat($claimTtlSeconds);
        self::assertIsInt($delayMs);

        $worstCaseRetries = ($claimTtlSeconds * 1000) / $delayMs;

        self::assertLessThanOrEqual(
            100,
            $worstCaseRetries,
            'A message stuck behind an expired claim would be retried too often before the claim expires.',
        );
    }

    /**
     * Drives a genuine claim race through DeduplicatingMiddleware (mirrors
     * DeduplicatingMiddlewareTest::testAConcurrentDuplicateCannotAlsoExecute) and returns the
     * real exception the losing side throws.
     */
    private function captureContentionException(): RecoverableMessageHandlingException
    {
        $cache = new ArrayAdapter();
        $lockStore = new InMemoryStore();
        $worker = static fn () => new DeduplicatingMiddleware($cache, new LockFactory($lockStore));

        $envelope = new Envelope(new \stdClass(), [new IdempotencyStamp('abc'), new ReceivedStamp('async')]);
        $workerB = $worker();
        $terminalB = new CountingMiddleware();

        $conflict = null;
        $terminalA = new CallbackTerminal(function () use ($workerB, $terminalB, $envelope, &$conflict): void {
            try {
                $workerB->handle($envelope, $this->stackFor($terminalB));
            } catch (RecoverableMessageHandlingException $exception) {
                $conflict = $exception;
            }
        });

        $worker()->handle($envelope, $this->stackFor($terminalA));

        self::assertInstanceOf(RecoverableMessageHandlingException::class, $conflict);

        return $conflict;
    }

    private function stackFor(CountingMiddleware|CallbackTerminal $next): StackInterface
    {
        $stack = $this->createStub(StackInterface::class);
        $stack->method('next')->willReturn($next);

        return $stack;
    }
}
