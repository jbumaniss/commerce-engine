<?php

declare(strict_types=1);

namespace App\Catalog\Application\Query;

final readonly class ListProducts
{
    public function __construct(
        public int $page,
        public int $perPage,
    ) {
    }
}
