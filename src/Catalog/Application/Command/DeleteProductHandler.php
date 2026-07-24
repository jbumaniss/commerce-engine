<?php

declare(strict_types=1);

namespace App\Catalog\Application\Command;

use App\Catalog\Domain\Repository\ProductRepositoryInterface;

final readonly class DeleteProductHandler
{
    public function __construct(
        private ProductRepositoryInterface $products,
    ) {
    }

    public function __invoke(DeleteProduct $command): bool
    {
        $product = $this->products->findById($command->id);

        if (null === $product) {
            return false;
        }

        $this->products->remove($product);

        return true;
    }
}
