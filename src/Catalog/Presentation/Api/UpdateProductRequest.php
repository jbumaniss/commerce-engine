<?php

declare(strict_types=1);

namespace App\Catalog\Presentation\Api;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateProductRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $name,

        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        #[Assert\Regex(
            pattern: '/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            message: 'The slug must contain lowercase letters, numbers, and hyphens only.',
        )]
        public string $slug,

        #[Assert\PositiveOrZero]
        public int $priceAmount,

        #[Assert\NotBlank]
        #[Assert\Currency]
        public string $currency,

        #[Assert\Length(max: 5000)]
        public ?string $description = null,
    ) {
    }
}
