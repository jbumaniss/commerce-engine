<?php

declare(strict_types=1);

namespace App\Catalog\Application\Query;

use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;

final readonly class ListProductsHandler
{
    public function __construct(
        private ProductRepositoryInterface $products,
    ) {
    }

    /**
     * @return Product[]
     */
    public function __invoke(ListProducts $query): array
    {
        return $this->products->findAllActive();
    }
}
