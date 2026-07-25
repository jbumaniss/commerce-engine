<?php

declare(strict_types=1);

namespace App\Tests\Catalog\Application\Command;

use App\Catalog\Application\Command\CreateProduct;
use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\Event\ProductWasCreated;
use App\Shared\Application\CommandBus;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

final class CreateProductEventTest extends KernelTestCase
{
    public function testCreatingAProductPublishesProductWasCreatedOnTheEventBus(): void
    {
        // Dispatched in-process (no HTTP round-trip) so the in-memory transport, which resets
        // on kernel reboot, still holds the published event when we inspect it.
        $product = $this->commandBus()->handle(new CreateProduct(
            name: 'PlayStation 5',
            slug: 'playstation-5',
            priceAmount: 49999,
            currency: 'EUR',
            description: null,
        ));

        self::assertInstanceOf(Product::class, $product);

        $sent = $this->eventTransport()->getSent();

        self::assertCount(1, $sent);

        $event = $sent[0]->getMessage();

        self::assertInstanceOf(ProductWasCreated::class, $event);
        self::assertSame($product->id(), $event->productId);
    }

    private function commandBus(): CommandBus
    {
        $bus = self::getContainer()->get(CommandBus::class);

        self::assertInstanceOf(CommandBus::class, $bus);

        return $bus;
    }

    private function eventTransport(): InMemoryTransport
    {
        $transport = self::getContainer()->get('messenger.transport.async');

        self::assertInstanceOf(InMemoryTransport::class, $transport);

        return $transport;
    }
}
