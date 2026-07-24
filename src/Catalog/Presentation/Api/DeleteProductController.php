<?php

declare(strict_types=1);

namespace App\Catalog\Presentation\Api;

use App\Catalog\Application\Command\DeleteProduct;
use App\Shared\Application\CommandBus;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final readonly class DeleteProductController
{
    public function __construct(
        private CommandBus $commandBus,
    ) {
    }

    #[Route('/api/products/{id}', name: 'api_products_delete', methods: ['DELETE'])]
    public function __invoke(int $id): Response
    {
        $deleted = $this->commandBus->handle(new DeleteProduct($id));

        if (true !== $deleted) {
            throw new NotFoundHttpException('Product not found.');
        }

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
