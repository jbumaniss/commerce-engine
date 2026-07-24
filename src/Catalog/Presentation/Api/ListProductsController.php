<?php

declare(strict_types=1);

namespace App\Catalog\Presentation\Api;

use App\Catalog\Application\Query\ListProducts;
use App\Catalog\Application\Query\ListProductsHandler;
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

        return new JsonResponse(ProductResponse::collection($products));
    }
}
