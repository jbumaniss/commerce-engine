<?php

declare(strict_types=1);

namespace App\Tests\Notification\Application;

use App\Notification\Application\NotificationChannel;
use App\Notification\Application\Notifier;
use App\Notification\Domain\Notification;
use PHPUnit\Framework\TestCase;

final class NotifierTest extends TestCase
{
    public function testItDeliversToEachDeclaredChannel(): void
    {
        $email = new RecordingChannel('email');
        $sms = new RecordingChannel('sms');

        (new Notifier([$email, $sms]))->send($this->notificationFor(['email']));

        self::assertSame(1, $email->count);
        self::assertSame(0, $sms->count, 'A channel not declared by the notification must not be used.');
    }

    public function testItFailsFastOnAnUnknownChannel(): void
    {
        $notifier = new Notifier([new RecordingChannel('email')]);

        $this->expectException(\RuntimeException::class);

        $notifier->send($this->notificationFor(['push']));
    }

    /**
     * @param list<string> $channels
     */
    private function notificationFor(array $channels): Notification
    {
        return new class($channels) implements Notification {
            /**
             * @param list<string> $channels
             */
            public function __construct(private array $channels)
            {
            }

            public function recipient(): string
            {
                return 'someone@example.test';
            }

            public function channels(): array
            {
                return $this->channels;
            }
        };
    }
}

final class RecordingChannel implements NotificationChannel
{
    public int $count = 0;

    public function __construct(private readonly string $name)
    {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function send(Notification $notification): void
    {
        ++$this->count;
    }
}
