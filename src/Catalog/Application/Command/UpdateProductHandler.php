<?php

declare(strict_types=1);

namespace App\Catalog\Application\Command;

use App\Catalog\Application\Exception\ProductSlugAlreadyExists;
use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class UpdateProductHandler
{
    public function __construct(
        private ProductRepositoryInterface $products,
    ) {
    }

    public function __invoke(UpdateProduct $command): ?Product
    {
        $product = $this->products->findById($command->id);

        if (null === $product) {
            return null;
        }

        $existing = $this->products->findBySlug($command->slug);

        if (null !== $existing && $existing->id() !== $command->id) {
            throw ProductSlugAlreadyExists::withSlug($command->slug);
        }

        $product->update(
            name: $command->name,
            slug: $command->slug,
            priceAmount: $command->priceAmount,
            currency: $command->currency,
            description: $command->description,
        );

        $this->products->save($product);

        return $product;
    }
}
