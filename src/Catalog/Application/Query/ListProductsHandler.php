<?php

declare(strict_types=1);

namespace App\Catalog\Application\Query;

use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;
use App\Shared\Application\Paginated;

final readonly class ListProductsHandler
{
    public function __construct(
        private ProductRepositoryInterface $products,
    ) {
    }

    /**
     * @return Paginated<Product>
     */
    public function __invoke(ListProducts $query): Paginated
    {
        $offset = ($query->page - 1) * $query->perPage;

        return new Paginated(
            $this->products->findActive($query->perPage, $offset),
            $query->page,
            $query->perPage,
            $this->products->countActive(),
        );
    }
}
