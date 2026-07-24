<?php

declare(strict_types=1);

namespace App\Catalog\Application\Command;

final readonly class CreateProduct
{
    public function __construct(
        public string $name,
        public string $slug,
        public int $priceAmount,
        public string $currency,
        public ?string $description = null,
    ) {
    }
}
