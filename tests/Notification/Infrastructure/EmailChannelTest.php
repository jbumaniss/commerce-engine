<?php

declare(strict_types=1);

namespace App\Tests\Notification\Infrastructure;

use App\Notification\Domain\ProductCreatedNotification;
use App\Notification\Infrastructure\EmailChannel;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\BodyRendererInterface;
use Symfony\Component\Mime\RawMessage;

final class EmailChannelTest extends KernelTestCase
{
    public function testItRendersAndSendsALocalisedEmailNotification(): void
    {
        $email = $this->send(new ProductCreatedNotification(42, 'catalog@example.test', 'New product created', 'en'));

        self::assertSame('New product created', $email->getSubject());
        self::assertSame('catalog@example.test', $email->getTo()[0]->getAddress());
        self::assertSame('no-reply@example.test', $email->getFrom()[0]->getAddress());

        $this->render($email);
        self::assertStringContainsString('A new product (ID: 42) has been created.', (string) $email->getTextBody());
    }

    public function testItLocalisesTheNotificationBody(): void
    {
        $email = $this->send(new ProductCreatedNotification(7, 'catalog@example.test', 'Nouveau produit créé', 'fr'));

        $this->render($email);
        self::assertStringContainsString('Un nouveau produit (ID : 7) a été créé.', (string) $email->getTextBody());
    }

    private function send(ProductCreatedNotification $notification): TemplatedEmail
    {
        $captured = null;

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())
            ->method('send')
            ->willReturnCallback(function (RawMessage $message) use (&$captured): void {
                $captured = $message;
            });

        (new EmailChannel($mailer, 'no-reply@example.test'))->send($notification);

        self::assertInstanceOf(TemplatedEmail::class, $captured);

        return $captured;
    }

    private function render(TemplatedEmail $email): void
    {
        $renderer = self::getContainer()->get(BodyRendererInterface::class);
        self::assertInstanceOf(BodyRendererInterface::class, $renderer);

        $renderer->render($email);
    }
}
