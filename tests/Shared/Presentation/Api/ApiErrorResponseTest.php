<?php

declare(strict_types=1);

namespace App\Tests\Shared\Presentation\Api;

use App\Tests\Support\ApiTestCase;

final class ApiErrorResponseTest extends ApiTestCase
{
    public function testNotFoundReturnsProblemJson(): void
    {
        $this->client->request('GET', '/api/products/999999');

        $this->assertStatusCode(404);

        $problem = $this->problem();

        self::assertSame('about:blank', $problem['type']);
        self::assertSame('Not Found', $problem['title']);
        self::assertSame(404, $problem['status']);
        self::assertNotEmpty($problem['detail']);
    }

    public function testConflictReturnsProblemJson(): void
    {
        $payload = [
            'name' => 'PlayStation 5',
            'slug' => 'playstation-5',
            'priceAmount' => 49999,
            'currency' => 'EUR',
            'description' => 'Current-generation console.',
        ];

        $this->postJson('/api/products', $payload);

        $this->assertCreated();

        $this->client->jsonRequest('POST', '/api/products', $payload);

        $this->assertStatusCode(409);

        $problem = $this->problem();

        self::assertSame('Conflict', $problem['title']);
        self::assertSame(409, $problem['status']);
        self::assertNotEmpty($problem['detail']);
    }

    public function testValidationErrorReturnsProblemJsonWithViolations(): void
    {
        $this->client->jsonRequest('POST', '/api/products', [
            'name' => '',
            'slug' => 'Not A Slug',
            'priceAmount' => -1,
            'currency' => 'EUR',
        ]);

        $this->assertStatusCode(422);

        $problem = $this->problem();

        self::assertSame(422, $problem['status']);
        self::assertNotEmpty($problem['title']);
        self::assertSame('Validation failed.', $problem['detail']);

        self::assertArrayHasKey('violations', $problem);
        self::assertIsArray($problem['violations']);
        self::assertNotEmpty($problem['violations']);

        foreach ($problem['violations'] as $violation) {
            self::assertIsArray($violation);
            self::assertArrayHasKey('property', $violation);
            self::assertArrayHasKey('message', $violation);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function problem(): array
    {
        $response = $this->client->getResponse();

        self::assertSame('application/problem+json', $response->headers->get('Content-Type'));

        $data = json_decode(
            (string) $response->getContent(),
            true,
            flags: \JSON_THROW_ON_ERROR,
        );

        self::assertIsArray($data);

        /* @var array<string, mixed> $data */
        return $data;
    }
}
