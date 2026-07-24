<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Repository;

final readonly class ProductFilter
{
    public function __construct(
        public ?string $name = null,
        public ?string $slug = null,
        public ?bool $isActive = null,
    ) {
    }
}
