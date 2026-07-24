<?php

declare(strict_types=1);

namespace App\Tests\Shared\Application;

use App\Catalog\Application\Command\CreateProduct;
use App\Catalog\Application\Exception\ProductSlugAlreadyExists;
use App\Catalog\Domain\Entity\Product;
use App\Shared\Application\CommandBus;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CommandBusTest extends KernelTestCase
{
    public function testItHandlesACommandSynchronouslyAndReturnsTheHandlerResult(): void
    {
        $product = $this->commandBus()->handle(new CreateProduct(
            name: 'PlayStation 5',
            slug: 'playstation-5',
            priceAmount: 49999,
            currency: 'EUR',
            description: null,
        ));

        self::assertInstanceOf(Product::class, $product);
        // Handled in-process: the product is already persisted when handle() returns.
        self::assertNotNull($product->id());
    }

    public function testItUnwrapsHandlerExceptionsToTheOriginalException(): void
    {
        $bus = $this->commandBus();

        $bus->handle(new CreateProduct(
            name: 'PlayStation 5',
            slug: 'playstation-5',
            priceAmount: 49999,
            currency: 'EUR',
            description: null,
        ));

        // The original application exception surfaces, not Messenger's HandlerFailedException,
        // so the RFC 7807 listener still maps it to 409.
        $this->expectException(ProductSlugAlreadyExists::class);

        $bus->handle(new CreateProduct(
            name: 'PlayStation 5 Duplicate',
            slug: 'playstation-5',
            priceAmount: 59999,
            currency: 'EUR',
            description: null,
        ));
    }

    private function commandBus(): CommandBus
    {
        $bus = self::getContainer()->get(CommandBus::class);

        self::assertInstanceOf(CommandBus::class, $bus);

        return $bus;
    }
}
