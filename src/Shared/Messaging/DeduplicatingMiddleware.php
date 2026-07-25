<?php

declare(strict_types=1);

namespace App\Shared\Messaging;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\RecoverableMessageHandlingException;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;

/**
 * Deduplicates consumed messages carrying an {@see IdempotencyStamp} (CE-025).
 *
 * Guarantee — at-least-once delivery with duplicate suppression, not exactly-once:
 *  - After a delivery has been handled successfully, later redeliveries of the same message id
 *    are acknowledged without running the handlers again (until the completion marker expires).
 *  - Concurrent duplicate deliveries cannot both run: workers race for an atomic, TTL-bounded
 *    claim (Redis lock); the loser is deferred with a recoverable exception, so Messenger
 *    redelivers it later — by then it is either suppressed (winner succeeded) or processed
 *    (winner failed and released the claim).
 *  - A failed handling releases the claim without writing the marker, so retries keep working.
 *  - Remaining window: a worker that crashes after the handlers ran but before the marker was
 *    written will cause one re-execution on redelivery; the claim TTL also bounds how long a
 *    crashed worker can block a message.
 *
 * Losing the claim race is reported as a RecoverableMessageHandlingException carrying an
 * explicit retry delay (CONTENTION_RETRY_DELAY_MS): without it, the delay would silently fall
 * back to the async transport's business retry_strategy (CE-022), coupling this concurrency
 * concern to an unrelated failure-handling policy that is free to change independently. The
 * exception's default forceRetry=true means contention bypasses the transport's max_retries, so
 * it can never exhaust retries into the dead-letter queue on its own.
 */
final readonly class DeduplicatingMiddleware implements MiddlewareInterface
{
    /**
     * Upper bound on how long a crashed worker's claim can outlive it. Must comfortably exceed
     * a single handling's duration; kept far below the completion marker's lifetime.
     */
    private const float CLAIM_TTL = 300.0;

    /**
     * Fixed backoff for a message that lost the claim race. It is intentionally flat (not
     * exponential) — the claim is released as soon as the winner finishes, so there is nothing
     * to back off from further, and a bespoke growing-delay strategy here would be an
     * unnecessary retry subsystem.
     *
     * Value chosen against the worst case, not the typical case: if the claim holder crashes
     * without releasing, the loser retries until CLAIM_TTL expires. At a naively short delay
     * (e.g. 500ms) that worst case is CLAIM_TTL / delay = 300s / 0.5s = 600 retries per stuck
     * message — a sustained ~2 req/s hammering Redis, the log and the transport for up to five
     * minutes, multiplied by however many messages are stuck at once. At 5000ms the same worst
     * case is 300s / 5s = 60 retries (~1 every 5s): a 10x reduction in worst-case volume. The
     * cost is added latency in the rare *genuine* near-simultaneous-duplicate case (the loser
     * waits up to 5s before rechecking instead of 0.5s), which is acceptable here — this is
     * async, eventually-consistent event processing with nothing synchronously waiting on it.
     */
    private const int CONTENTION_RETRY_DELAY_MS = 5000;

    public function __construct(
        private CacheItemPoolInterface $messengerDeduplicationCache,
        private LockFactory $lockFactory,
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $stamp = $envelope->last(IdempotencyStamp::class);

        // Only deduplicate on the consuming side; on dispatch there is nothing to redeliver.
        if (null === $stamp || null === $envelope->last(ReceivedStamp::class)) {
            return $stack->next()->handle($envelope, $stack);
        }

        $key = $this->key($stamp->id);

        if ($this->messengerDeduplicationCache->getItem($key)->isHit()) {
            // Already processed by an earlier delivery: acknowledge without handling again.
            return $envelope;
        }

        // Atomic cross-worker claim (Redis SET NX under the hood): at most one worker may
        // process this message id at a time.
        $claim = $this->lockFactory->createLock('messenger_dedup_claim_'.$stamp->id, self::CLAIM_TTL);

        if (!$claim->acquire()) {
            // Another worker is handling this exact message right now. Defer instead of
            // skipping: if that worker fails, this redelivery must still be able to run.
            throw new RecoverableMessageHandlingException(sprintf('Message "%s" is currently being processed by another worker.', $stamp->id), retryDelay: self::CONTENTION_RETRY_DELAY_MS);
        }

        try {
            // Re-check under the claim: the concurrent holder may have completed between the
            // first check and our acquisition.
            $item = $this->messengerDeduplicationCache->getItem($key);

            if ($item->isHit()) {
                return $envelope;
            }

            $envelope = $stack->next()->handle($envelope, $stack);

            // Marked only after success, so a failed handling stays retryable.
            $this->messengerDeduplicationCache->save($item->set(true));

            return $envelope;
        } finally {
            $claim->release();
        }
    }

    private function key(string $id): string
    {
        return 'messenger_dedup_'.sha1($id);
    }
}
