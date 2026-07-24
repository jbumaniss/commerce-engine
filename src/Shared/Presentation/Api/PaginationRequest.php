<?php

declare(strict_types=1);

namespace App\Shared\Presentation\Api;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class PaginationRequest
{
    public function __construct(
        #[Assert\Positive]
        public int $page = 1,

        #[Assert\Positive]
        #[Assert\LessThanOrEqual(100)]
        public int $perPage = 20,
    ) {
    }
}
