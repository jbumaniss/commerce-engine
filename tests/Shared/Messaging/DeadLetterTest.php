<?php

declare(strict_types=1);

namespace App\Tests\Shared\Messaging;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\EventListener\SendFailedMessageToFailureTransportListener;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

/**
 * A message whose retries are exhausted is dead-lettered (CE-023), then inspected/removed with the
 * failure-transport tooling (CE-024: messenger:failed:show/retry/remove work against this transport).
 */
final class DeadLetterTest extends KernelTestCase
{
    public function testAFailedMessageIsSentToTheFailureTransport(): void
    {
        $failed = $this->failedTransport();

        self::assertCount(0, $failed->getSent());

        $this->deadLetterAMessage();

        self::assertCount(1, $failed->getSent());
    }

    public function testFailedMessagesCanBeInspectedAndRemoved(): void
    {
        $failed = $this->failedTransport();

        $this->deadLetterAMessage();

        // Inspect: the failed message is retrievable from the dead-letter queue.
        $pending = [...$failed->get()];
        self::assertCount(1, $pending);

        // Remove: an operator can drop it from the dead-letter queue.
        $failed->reject($pending[0]);
        self::assertCount(0, [...$failed->get()]);
    }

    private function failedTransport(): InMemoryTransport
    {
        $failed = self::getContainer()->get('messenger.transport.failed');

        self::assertInstanceOf(InMemoryTransport::class, $failed);

        return $failed;
    }

    private function deadLetterAMessage(): void
    {
        $listener = self::getContainer()->get('messenger.failure.send_failed_message_to_failure_transport_listener');

        self::assertInstanceOf(SendFailedMessageToFailureTransportListener::class, $listener);

        // A worker reports a message that will not be retried (retries exhausted).
        $listener->onMessageFailed(
            new WorkerMessageFailedEvent(new Envelope(new \stdClass()), 'async', new \RuntimeException('boom')),
        );
    }
}
