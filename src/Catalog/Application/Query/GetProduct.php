<?php

declare(strict_types=1);

namespace App\Catalog\Application\Query;

final readonly class GetProduct
{
    public function __construct(
        public int $id,
    ) {
    }
}
