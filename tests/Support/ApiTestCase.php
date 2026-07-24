<?php

declare(strict_types=1);

namespace App\Tests\Support;

use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class ApiTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
    }

    protected function postJson(string $uri, array $payload): array
    {
        $this->client->jsonRequest('POST', $uri, $payload);

        return $this->json();
    }

    protected function getJson(string $uri): array
    {
        $this->client->request('GET', $uri);

        return $this->json();
    }

    protected function json(): array
    {
        $response = $this->client->getResponse();

        Assert::assertTrue(
            $response->headers->contains('Content-Type', 'application/json'),
            sprintf(
                'Expected JSON response, got "%s".',
                (string) $response->headers->get('Content-Type'),
            ),
        );

        return json_decode(
            $response->getContent(),
            true,
            flags: \JSON_THROW_ON_ERROR,
        );
    }

    protected function assertStatusCode(int $status): void
    {
        self::assertResponseStatusCodeSame($status);
    }

    protected function assertCreated(): void
    {
        $this->assertStatusCode(201);
    }

    protected function assertOk(): void
    {
        $this->assertStatusCode(200);
    }

    protected function assertJsonContains(array $expected, array $actual): void
    {
        foreach ($expected as $key => $value) {
            self::assertArrayHasKey($key, $actual);
            self::assertSame($value, $actual[$key]);
        }
    }
}
