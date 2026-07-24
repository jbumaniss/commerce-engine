<?php

declare(strict_types=1);

namespace App\Catalog\Application\Command;

use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;

final readonly class CreateProductHandler
{
    public function __construct(
        private ProductRepositoryInterface $products,
    ) {
    }

    public function __invoke(CreateProduct $command): Product
    {
        $product = new Product(
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
