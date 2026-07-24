<?php

declare(strict_types=1);

namespace App\Catalog\Presentation\Api;

use App\Catalog\Domain\Entity\Product;

/**
 * Maps a Product entity to its API response representation.
 *
 * Presentation-only: field mapping and formatting, no business logic.
 */
final readonly class ProductResponse
{
    /**
     * @return array<string, mixed>
     */
    public static function fromProduct(Product $product): array
    {
        return [
            'id' => $product->id(),
            'name' => $product->name(),
            'slug' => $product->slug(),
            'priceAmount' => $product->priceAmount(),
            'currency' => $product->currency(),
            'description' => $product->description(),
            'isActive' => $product->isActive(),
            'createdAt' => $product->createdAt()->format(\DateTimeInterface::ATOM),
            'updatedAt' => $product->updatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @param Product[] $products
     *
     * @return list<array<string, mixed>>
     */
    public static function collection(array $products): array
    {
        return array_values(array_map(self::fromProduct(...), $products));
    }
}
