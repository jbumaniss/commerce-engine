<?php

declare(strict_types=1);

namespace App\Shared\Presentation\Api;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies validation-based HTTP caching (RFC 9110 conditional requests) to a read response.
 *
 * The ETag is derived from the response body, so it changes whenever the representation
 * changes — including after a write. There is no stored cache to invalidate: a stale client
 * validator simply stops matching and the next conditional request revalidates to a fresh 200.
 */
final class HttpCache
{
    public static function conditional(Response $response, Request $request): void
    {
        $response->setEtag(hash('xxh128', (string) $response->getContent()));

        // Turns the response into an empty 304 when the client's validators still match.
        $response->isNotModified($request);
    }
}
