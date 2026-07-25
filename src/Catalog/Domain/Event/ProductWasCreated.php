<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Event;

/**
 * Domain event published when a product has been created. Consumers (e.g. notifications) are
 * added in later tickets; the event bus allows zero handlers for now.
 */
final readonly class ProductWasCreated
{
    public function __construct(
        public int $productId,
    ) {
    }
}
