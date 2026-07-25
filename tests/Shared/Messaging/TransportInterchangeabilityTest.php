<?php

declare(strict_types=1);

namespace App\Tests\Shared\Messaging;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;

/**
 * The async transport is env-driven and transport-agnostic: switching between Redis and an
 * SQS-compatible broker is a configuration (DSN) change only, with no application code change.
 */
final class TransportInterchangeabilityTest extends KernelTestCase
{
    public function testRedisAndSqsTransportsAreBothSelectableByConfiguration(): void
    {
        $factory = self::getContainer()->get('messenger.transport_factory');

        self::assertInstanceOf(TransportFactoryInterface::class, $factory);

        // Both DSN schemes are resolvable out of the box, so MESSENGER_TRANSPORT_DSN can point at
        // either broker without touching application code.
        self::assertTrue($factory->supports('redis://localhost:6379/messages', []));
        self::assertTrue($factory->supports('sqs://default/messages', []));
    }
}
