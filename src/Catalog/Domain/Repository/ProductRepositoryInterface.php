<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Repository;

use App\Catalog\Domain\Entity\Product;

interface ProductRepositoryInterface
{
    public function save(Product $product): void;

    public function remove(Product $product): void;

    public function findById(int $id): ?Product;

    public function findBySlug(string $slug): ?Product;

    /**
     * @return Product[]
     */
    public function findAllActive(): array;
}
