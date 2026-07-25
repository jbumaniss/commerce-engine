<?php

declare(strict_types=1);

namespace App\Tests\Shared\Messaging;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Retry\MultiplierRetryStrategy;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;

/**
 * The async transport retries transient failures with exponential backoff (CE-022).
 */
final class RetryStrategyTest extends KernelTestCase
{
    public function testTheAsyncTransportUsesExponentialBackoff(): void
    {
        $strategy = self::getContainer()->get('messenger.retry.multiplier_retry_strategy.async');

        self::assertInstanceOf(MultiplierRetryStrategy::class, $strategy);

        $envelope = new Envelope(new \stdClass());

        // Delays grow by the configured multiplier (2x): ~1s, ~2s, ~4s. Ranges absorb the
        // built-in jitter (±10%); the non-overlapping bands still prove exponential backoff.
        self::assertEqualsWithDelta(1000, $strategy->getWaitingTime($envelope), 100);
        self::assertEqualsWithDelta(2000, $strategy->getWaitingTime($envelope->with(new RedeliveryStamp(1))), 200);
        self::assertEqualsWithDelta(4000, $strategy->getWaitingTime($envelope->with(new RedeliveryStamp(2))), 400);

        // The message is retried up to the configured maximum (3), then rejected.
        self::assertTrue($strategy->isRetryable($envelope->with(new RedeliveryStamp(2))));
        self::assertFalse($strategy->isRetryable($envelope->with(new RedeliveryStamp(3))));
    }
}
