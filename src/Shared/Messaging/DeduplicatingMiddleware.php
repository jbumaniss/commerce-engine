<?php

declare(strict_types=1);

namespace App\Shared\Messaging;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;

/**
 * Makes async processing idempotent (CE-025): when a message carrying an {@see IdempotencyStamp}
 * is consumed from a transport, it is handled at most once. A redelivery of an already-processed
 * message is skipped, so at-least-once delivery is safe.
 *
 * The processed id is recorded only after successful handling, so a failed handling is still
 * retried (the message is not yet marked as processed).
 */
final readonly class DeduplicatingMiddleware implements MiddlewareInterface
{
    public function __construct(
        private CacheItemPoolInterface $messengerDeduplicationCache,
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $stamp = $envelope->last(IdempotencyStamp::class);

        // Only deduplicate on the consuming side; on dispatch there is nothing to redeliver.
        if (null === $stamp || null === $envelope->last(ReceivedStamp::class)) {
            return $stack->next()->handle($envelope, $stack);
        }

        $item = $this->messengerDeduplicationCache->getItem($this->key($stamp->id));

        if ($item->isHit()) {
            // Already processed by an earlier delivery: acknowledge without handling again.
            return $envelope;
        }

        $envelope = $stack->next()->handle($envelope, $stack);

        $this->messengerDeduplicationCache->save($item->set(true));

        return $envelope;
    }

    private function key(string $id): string
    {
        return 'messenger_dedup_'.sha1($id);
    }
}
