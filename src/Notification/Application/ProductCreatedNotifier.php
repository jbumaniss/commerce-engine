<?php

declare(strict_types=1);

namespace App\Notification\Application;

use App\Catalog\Domain\Event\ProductWasCreated;
use App\Notification\Domain\ProductCreatedNotification;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Turns the ProductWasCreated domain event into a channel-agnostic notification (CE-028) and hands
 * it to the {@see Notifier}. Runs asynchronously on the event bus (CE-026).
 */
#[AsMessageHandler(bus: 'event.bus')]
final readonly class ProductCreatedNotifier
{
    public function __construct(
        private Notifier $notifier,
        private TranslatorInterface $translator,
        private string $recipientEmail,
        private string $locale,
    ) {
    }

    public function __invoke(ProductWasCreated $event): void
    {
        $this->notifier->send(new ProductCreatedNotification(
            productId: $event->productId,
            recipient: $this->recipientEmail,
            subject: $this->translator->trans('email.product_created.subject', locale: $this->locale),
            locale: $this->locale,
        ));
    }
}
