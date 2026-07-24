<?php

declare(strict_types=1);

namespace App\Catalog\Application\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;

#[WithHttpStatus(Response::HTTP_CONFLICT)]
final class ProductSlugAlreadyExists extends \RuntimeException
{
    public static function withSlug(string $slug): self
    {
        return new self(sprintf('A product with slug "%s" already exists.', $slug));
    }
}
