<?php

declare(strict_types=1);

namespace App\Tests\Notification\Application;

use App\Catalog\Domain\Event\ProductWasCreated;
use App\Notification\Application\ProductCreatedEmailNotifier;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\BodyRendererInterface;
use Symfony\Component\Mime\RawMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ProductCreatedEmailNotifierTest extends KernelTestCase
{
    public function testItRendersALocalisedEmailFromTemplates(): void
    {
        $email = $this->notify(42, 'en');

        self::assertSame('New product created', $email->getSubject());

        $this->render($email);

        self::assertStringContainsString('A new product (ID: 42) has been created.', (string) $email->getTextBody());
        self::assertStringContainsString('42', (string) $email->getHtmlBody());
    }

    public function testItLocalisesTheEmailForAnotherLocale(): void
    {
        $email = $this->notify(7, 'fr');

        self::assertSame('Nouveau produit créé', $email->getSubject());

        $this->render($email);

        self::assertStringContainsString('Un nouveau produit (ID : 7) a été créé.', (string) $email->getTextBody());
    }

    private function notify(int $productId, string $locale): TemplatedEmail
    {
        $captured = null;

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())
            ->method('send')
            ->willReturnCallback(function (RawMessage $message) use (&$captured): void {
                $captured = $message;
            });

        $translator = self::getContainer()->get(TranslatorInterface::class);
        self::assertInstanceOf(TranslatorInterface::class, $translator);

        $notifier = new ProductCreatedEmailNotifier(
            $mailer,
            $translator,
            'no-reply@example.test',
            'catalog@example.test',
            $locale,
        );

        $notifier(new ProductWasCreated($productId));

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
