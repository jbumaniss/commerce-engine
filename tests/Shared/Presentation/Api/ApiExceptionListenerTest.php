<?php

declare(strict_types=1);

namespace App\Tests\Shared\Presentation\Api;

use App\Shared\Presentation\Api\ApiExceptionListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class ApiExceptionListenerTest extends TestCase
{
    public function testItHidesInternalDetailsForServerErrors(): void
    {
        $event = $this->event(
            new \RuntimeException('SQLSTATE connection failed: secret-dsn'),
            '/api/products',
        );

        (new ApiExceptionListener())($event);

        $response = $event->getResponse();

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(500, $response->getStatusCode());
        self::assertSame('application/problem+json', $response->headers->get('Content-Type'));

        $content = (string) $response->getContent();

        self::assertStringNotContainsStringIgnoringCase('secret-dsn', $content);

        $data = json_decode($content, true, flags: \JSON_THROW_ON_ERROR);

        self::assertIsArray($data);
        self::assertSame(500, $data['status']);
        self::assertSame('An unexpected error occurred.', $data['detail']);
    }

    public function testItIgnoresNonApiRequests(): void
    {
        $event = $this->event(new \RuntimeException('boom'), '/dashboard');

        (new ApiExceptionListener())($event);

        self::assertFalse($event->hasResponse());
    }

    private function event(\Throwable $throwable, string $path): ExceptionEvent
    {
        return new ExceptionEvent(
            $this->createStub(HttpKernelInterface::class),
            Request::create($path),
            HttpKernelInterface::MAIN_REQUEST,
            $throwable,
        );
    }
}
