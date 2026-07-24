<?php

declare(strict_types=1);

namespace App\Catalog\Application\Command;

use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;

final readonly class ChangeProductActivationHandler
{
    public function __construct(
        private ProductRepositoryInterface $products,
    ) {
    }

    public function __invoke(ChangeProductActivation $command): ?Product
    {
        $product = $this->products->findById($command->id);

        if (null === $product) {
            return null;
        }

        if ($command->active) {
            $product->activate();
        } else {
            $product->deactivate();
        }

        $this->products->save($product);

        return $product;
    }
}
