<?php

declare(strict_types=1);

namespace App\Catalog\Presentation\Api;

use App\Catalog\Application\Query\GetProduct;
use App\Catalog\Application\Query\GetProductHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final readonly class GetProductController
{
    public function __construct(
        private GetProductHandler $handler,
    ) {
    }

    #[Route('/api/products/{id}', name: 'api_products_get', methods: ['GET'])]
    public function __invoke(int $id): JsonResponse
    {
        $product = ($this->handler)(new GetProduct($id));

        if (null === $product) {
            throw new NotFoundHttpException('Product not found.');
        }

        return new JsonResponse([
            'id' => $product->id(),
            'name' => $product->name(),
            'slug' => $product->slug(),
            'priceAmount' => $product->priceAmount(),
            'currency' => $product->currency(),
            'description' => $product->description(),
            'isActive' => $product->isActive(),
            'createdAt' => $product->createdAt()->format(\DateTimeInterface::ATOM),
            'updatedAt' => $product->updatedAt()->format(\DateTimeInterface::ATOM),
        ]);
    }
}
