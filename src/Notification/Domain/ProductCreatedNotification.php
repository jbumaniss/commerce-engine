<?php

declare(strict_types=1);

namespace App\Notification\Domain;

/**
 * Notifies that a product was created. Delivered over email today; new channels can be added by
 * declaring them in {@see channels()} and providing the matching companion interface.
 */
final readonly class ProductCreatedNotification implements EmailNotification
{
    public function __construct(
        private int $productId,
        private string $recipient,
        private string $subject,
        private string $locale,
    ) {
    }

    public function recipient(): string
    {
        return $this->recipient;
    }

    public function channels(): array
    {
        return ['email'];
    }

    public function subject(): string
    {
        return $this->subject;
    }

    public function htmlTemplate(): string
    {
        return 'emails/product_created.html.twig';
    }

    public function textTemplate(): string
    {
        return 'emails/product_created.txt.twig';
    }

    public function locale(): string
    {
        return $this->locale;
    }

    public function context(): array
    {
        return ['productId' => $this->productId];
    }
}
