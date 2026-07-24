<?php

declare(strict_types=1);

namespace App\Catalog\Presentation\Api;

use App\Catalog\Application\Command\ChangeProductActivation;
use App\Catalog\Domain\Entity\Product;
use App\Shared\Application\CommandBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final readonly class DeactivateProductController
{
    public function __construct(
        private CommandBus $commandBus,
    ) {
    }

    #[Route('/api/products/{id}/deactivate', name: 'api_products_deactivate', methods: ['POST'])]
    public function __invoke(int $id): JsonResponse
    {
        $product = $this->commandBus->handle(new ChangeProductActivation($id, active: false));

        if (!$product instanceof Product) {
            throw new NotFoundHttpException('Product not found.');
        }

        return new JsonResponse(ProductResponse::fromProduct($product));
    }
}
