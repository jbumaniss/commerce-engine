<?php

declare(strict_types=1);

namespace App\Catalog\Presentation\Api;

use App\Catalog\Application\Command\ChangeProductActivation;
use App\Catalog\Application\Command\ChangeProductActivationHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final readonly class ActivateProductController
{
    public function __construct(
        private ChangeProductActivationHandler $handler,
    ) {
    }

    #[Route('/api/products/{id}/activate', name: 'api_products_activate', methods: ['POST'])]
    public function __invoke(int $id): JsonResponse
    {
        $product = ($this->handler)(new ChangeProductActivation($id, active: true));

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
