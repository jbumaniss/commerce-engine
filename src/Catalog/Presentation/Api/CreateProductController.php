<?php

declare(strict_types=1);

namespace App\Catalog\Presentation\Api;

use App\Catalog\Application\Command\CreateProduct;
use App\Catalog\Domain\Entity\Product;
use App\Shared\Application\CommandBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final readonly class CreateProductController
{
    public function __construct(
        private CommandBus $commandBus,
    ) {
    }

    #[Route('/api/products', name: 'api_products_create', methods: ['POST'])]
    public function __invoke(
        #[MapRequestPayload] CreateProductRequest $request,
    ): JsonResponse {
        $product = $this->commandBus->handle(new CreateProduct(
            name: $request->name,
            slug: $request->slug,
            priceAmount: $request->priceAmount,
            currency: $request->currency,
            description: $request->description,
        ));

        \assert($product instanceof Product);

        return new JsonResponse(
            data: ProductResponse::fromProduct($product),
            status: JsonResponse::HTTP_CREATED,
        );
    }
}
