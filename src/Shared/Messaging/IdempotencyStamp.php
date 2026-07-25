<?php

declare(strict_types=1);

namespace App\Shared\Messaging;

use Symfony\Component\Messenger\Stamp\StampInterface;

/**
 * Carries a stable, per-dispatch unique id so a redelivered message can be recognised and
 * deduplicated by {@see DeduplicatingMiddleware}.
 */
final readonly class IdempotencyStamp implements StampInterface
{
    public function __construct(
        public string $id,
    ) {
    }
}
