<?php

declare(strict_types=1);

namespace App\Catalog\Application\Command;

use App\Catalog\Application\Exception\ProductSlugAlreadyExists;
use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\Event\ProductWasCreated;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;
use App\Shared\Application\EventBus;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CreateProductHandler
{
    public function __construct(
        private ProductRepositoryInterface $products,
        private EventBus $events,
    ) {
    }

    public function __invoke(CreateProduct $command): Product
    {
        if (null !== $this->products->findBySlug($command->slug)) {
            throw ProductSlugAlreadyExists::withSlug($command->slug);
        }

        $product = new Product(
            name: $command->name,
            slug: $command->slug,
            priceAmount: $command->priceAmount,
            currency: $command->currency,
            description: $command->description,
        );

        $this->products->save($product);

        $id = $product->id();
        \assert(null !== $id);

        $this->events->dispatch(new ProductWasCreated($id));

        return $product;
    }
}
