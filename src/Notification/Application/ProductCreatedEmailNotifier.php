<?php

declare(strict_types=1);

namespace App\Notification\Application;

use App\Catalog\Domain\Event\ProductWasCreated;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Sends a notification email when a product is created. Registered on the event bus, so it runs
 * asynchronously when the worker consumes the ProductWasCreated event (CE-026).
 *
 * The body is rendered from reusable, localisable Twig templates and the subject is translated
 * (CE-027); the locale drives both.
 */
#[AsMessageHandler(bus: 'event.bus')]
final readonly class ProductCreatedEmailNotifier
{
    public function __construct(
        private MailerInterface $mailer,
        private TranslatorInterface $translator,
        private string $fromEmail,
        private string $recipientEmail,
        private string $locale,
    ) {
    }

    public function __invoke(ProductWasCreated $event): void
    {
        $email = (new TemplatedEmail())
            ->from($this->fromEmail)
            ->to($this->recipientEmail)
            ->subject($this->translator->trans('email.product_created.subject', locale: $this->locale))
            ->htmlTemplate('emails/product_created.html.twig')
            ->textTemplate('emails/product_created.txt.twig')
            ->locale($this->locale)
            ->context(['productId' => $event->productId]);

        $this->mailer->send($email);
    }
}
