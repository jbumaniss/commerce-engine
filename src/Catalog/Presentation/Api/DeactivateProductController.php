<?php

declare(strict_types=1);

namespace App\Catalog\Presentation\Api;

use App\Catalog\Application\Command\ChangeProductActivation;
use App\Catalog\Application\Command\ChangeProductActivationHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final readonly class DeactivateProductController
{
    public function __construct(
        private ChangeProductActivationHandler $handler,
    ) {
    }

    #[Route('/api/products/{id}/deactivate', name: 'api_products_deactivate', methods: ['POST'])]
    public function __invoke(int $id): JsonResponse
    {
        $product = ($this->handler)(new ChangeProductActivation($id, active: false));

        if (null === $product) {
            throw new NotFoundHttpException('Product not found.');
        }

        return new JsonResponse(ProductResponse::fromProduct($product));
    }
}
