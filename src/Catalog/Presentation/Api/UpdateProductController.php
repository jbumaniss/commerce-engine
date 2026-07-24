<?php

declare(strict_types=1);

namespace App\Catalog\Presentation\Api;

use App\Catalog\Application\Command\UpdateProduct;
use App\Catalog\Application\Command\UpdateProductHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final readonly class UpdateProductController
{
    public function __construct(
        private UpdateProductHandler $handler,
    ) {
    }

    #[Route('/api/products/{id}', name: 'api_products_update', methods: ['PUT'])]
    public function __invoke(
        int $id,
        #[MapRequestPayload] UpdateProductRequest $request,
    ): JsonResponse {
        $product = ($this->handler)(new UpdateProduct(
            id: $id,
            name: $request->name,
            slug: $request->slug,
            priceAmount: $request->priceAmount,
            currency: $request->currency,
            description: $request->description,
        ));

        if (null === $product) {
            throw new NotFoundHttpException('Product not found.');
        }

        return new JsonResponse(ProductResponse::fromProduct($product));
    }
}
