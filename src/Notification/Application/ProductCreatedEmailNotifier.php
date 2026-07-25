<?php

declare(strict_types=1);

namespace App\Notification\Application;

use App\Catalog\Domain\Event\ProductWasCreated;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

/**
 * Sends a notification email when a product is created. Registered on the event bus, so it runs
 * asynchronously when the worker consumes the ProductWasCreated event (CE-026).
 */
#[AsMessageHandler(bus: 'event.bus')]
final readonly class ProductCreatedEmailNotifier
{
    public function __construct(
        private MailerInterface $mailer,
        private string $fromEmail,
        private string $recipientEmail,
    ) {
    }

    public function __invoke(ProductWasCreated $event): void
    {
        $email = (new Email())
            ->from($this->fromEmail)
            ->to($this->recipientEmail)
            ->subject('New product created')
            ->text(sprintf('A new product (ID: %d) has been created.', $event->productId));

        $this->mailer->send($email);
    }
}
