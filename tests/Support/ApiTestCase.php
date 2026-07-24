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

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     *
     * @throws \JsonException
     */
    protected function postJson(string $uri, array $payload): array
    {
        $this->client->jsonRequest('POST', $uri, $payload);

        return $this->json();
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     *
     * @throws \JsonException
     */
    protected function putJson(string $uri, array $payload): array
    {
        $this->client->jsonRequest('PUT', $uri, $payload);

        return $this->json();
    }

    /**
     * @return array<string, mixed>
     *
     * @throws \JsonException
     */
    protected function getJson(string $uri): array
    {
        $this->client->request('GET', $uri);

        return $this->json();
    }

    /**
     * @return array<string, mixed>
     *
     * @throws \JsonException
     */
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

        $content = $response->getContent();

        Assert::assertIsString($content);

        $data = json_decode(
            $content,
            true,
            flags: \JSON_THROW_ON_ERROR,
        );

        Assert::assertIsArray($data);

        return $data;
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

    /**
     * @param array<string, mixed> $expected
     * @param array<string, mixed> $actual
     */
    protected function assertJsonContains(array $expected, array $actual): void
    {
        foreach ($expected as $key => $value) {
            self::assertArrayHasKey($key, $actual);
            self::assertSame($value, $actual[$key]);
        }
    }
}
