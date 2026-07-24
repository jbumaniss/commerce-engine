<?php

declare(strict_types=1);

namespace App\Shared\Application;

/**
 * A page of results together with its pagination metadata.
 *
 * @template T
 */
final readonly class Paginated
{
    /**
     * @param T[] $items
     */
    public function __construct(
        public array $items,
        public int $page,
        public int $perPage,
        public int $total,
    ) {
    }

    public function totalPages(): int
    {
        if ($this->perPage < 1) {
            return 0;
        }

        return (int) ceil($this->total / $this->perPage);
    }
}
