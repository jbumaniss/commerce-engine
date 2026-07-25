<?php

declare(strict_types=1);

namespace App\Tests\Notification\Application;

use App\Catalog\Domain\Event\ProductWasCreated;
use App\Notification\Application\ProductCreatedEmailNotifier;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

final class ProductCreatedEmailNotifierTest extends TestCase
{
    public function testItSendsANotificationEmailForACreatedProduct(): void
    {
        $captured = null;

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())
            ->method('send')
            ->willReturnCallback(function (RawMessage $message) use (&$captured): void {
                $captured = $message;
            });

        $notifier = new ProductCreatedEmailNotifier($mailer, 'no-reply@example.test', 'catalog@example.test');

        $notifier(new ProductWasCreated(42));

        self::assertInstanceOf(Email::class, $captured);
        self::assertSame('New product created', $captured->getSubject());
        self::assertSame('catalog@example.test', $captured->getTo()[0]->getAddress());
        self::assertSame('no-reply@example.test', $captured->getFrom()[0]->getAddress());
        self::assertStringContainsString('42', (string) $captured->getTextBody());
    }
}
