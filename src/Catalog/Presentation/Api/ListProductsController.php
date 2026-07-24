<?php

declare(strict_types=1);

namespace App\Catalog\Presentation\Api;

use App\Catalog\Application\Query\ListProducts;
use App\Catalog\Application\Query\ListProductsHandler;
use App\Shared\Presentation\Api\PaginationRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

final readonly class ListProductsController
{
    public function __construct(
        private ListProductsHandler $handler,
    ) {
    }

    #[Route('/api/products', name: 'api_products_list', methods: ['GET'])]
    public function __invoke(
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        PaginationRequest $pagination = new PaginationRequest(),
    ): JsonResponse {
        $result = ($this->handler)(new ListProducts($pagination->page, $pagination->perPage));

        return new JsonResponse([
            'items' => ProductResponse::collection($result->items),
            'page' => $result->page,
            'perPage' => $result->perPage,
            'total' => $result->total,
            'totalPages' => $result->totalPages(),
        ]);
    }
}
