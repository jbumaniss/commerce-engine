<?php

declare(strict_types=1);

namespace App\Catalog\Application\Query;

use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;

final readonly class GetProductHandler
{
    public function __construct(
        private ProductRepositoryInterface $products,
    ) {
    }

    public function __invoke(GetProduct $query): ?Product
    {
        return $this->products->findById($query->id);
    }
}
