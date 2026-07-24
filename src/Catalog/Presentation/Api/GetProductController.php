<?php

declare(strict_types=1);

namespace App\Catalog\Presentation\Api;

use App\Catalog\Application\Query\GetProduct;
use App\Catalog\Application\Query\GetProductHandler;
use App\Shared\Presentation\Api\HttpCache;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final readonly class GetProductController
{
    public function __construct(
        private GetProductHandler $handler,
    ) {
    }

    #[Route('/api/products/{id}', name: 'api_products_get', methods: ['GET'])]
    public function __invoke(int $id, Request $request): JsonResponse
    {
        $product = ($this->handler)(new GetProduct($id));

        if (null === $product) {
            throw new NotFoundHttpException('Product not found.');
        }

        $response = new JsonResponse(ProductResponse::fromProduct($product));
        $response->setLastModified($product->updatedAt());

        HttpCache::conditional($response, $request);

        return $response;
    }
}
