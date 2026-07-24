<?php

declare(strict_types=1);

namespace App\Catalog\Presentation\Api;

use App\Catalog\Application\Query\ListProducts;
use App\Catalog\Application\Query\ListProductsHandler;
use App\Catalog\Domain\Entity\Product;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class ListProductsController
{
    public function __construct(
        private ListProductsHandler $handler,
    ) {
    }

    #[Route('/api/products', name: 'api_products_list', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $products = ($this->handler)(new ListProducts());

        return new JsonResponse(array_map(
            static fn (Product $product): array => [
                'id' => $product->id(),
                'name' => $product->name(),
                'slug' => $product->slug(),
                'priceAmount' => $product->priceAmount(),
                'currency' => $product->currency(),
                'description' => $product->description(),
                'isActive' => $product->isActive(),
                'createdAt' => $product->createdAt()->format(\DateTimeInterface::ATOM),
                'updatedAt' => $product->updatedAt()->format(\DateTimeInterface::ATOM),
            ],
            $products,
        ));
    }
}
