<?php

declare(strict_types=1);

namespace App\Tests\Shared\Messaging;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\EventListener\SendFailedMessageToFailureTransportListener;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

/**
 * A message whose retries are exhausted is routed to the dead-letter (failed) transport (CE-023).
 */
final class DeadLetterTest extends KernelTestCase
{
    public function testAFailedMessageIsSentToTheFailureTransport(): void
    {
        $failed = self::getContainer()->get('messenger.transport.failed');
        self::assertInstanceOf(InMemoryTransport::class, $failed);

        $listener = self::getContainer()->get('messenger.failure.send_failed_message_to_failure_transport_listener');
        self::assertInstanceOf(SendFailedMessageToFailureTransportListener::class, $listener);

        // A worker reports a message that will not be retried (retries exhausted).
        $event = new WorkerMessageFailedEvent(new Envelope(new \stdClass()), 'async', new \RuntimeException('boom'));

        self::assertCount(0, $failed->getSent());

        $listener->onMessageFailed($event);

        // It has been dead-lettered.
        self::assertCount(1, $failed->getSent());
    }
}
