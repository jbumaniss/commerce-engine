<?php

declare(strict_types=1);

namespace App\Catalog\Presentation\Api;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ProductListCriteria
{
    public function __construct(
        public ?string $name = null,
        public ?string $slug = null,
        public ?bool $isActive = null,

        #[Assert\Choice(choices: ['id', 'name', 'slug', 'createdAt'])]
        public string $sort = 'id',

        #[Assert\Choice(choices: ['asc', 'desc'])]
        public string $direction = 'asc',
    ) {
    }
}
