<?php

declare(strict_types=1);

namespace App\Catalog\Application\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;

#[WithHttpStatus(Response::HTTP_CONFLICT)]
final class ProductWasConcurrentlyModified extends \RuntimeException
{
    public static function create(): self
    {
        return new self('The product was modified by another request. Please reload it and try again.');
    }
}
