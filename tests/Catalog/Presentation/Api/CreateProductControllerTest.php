<?php

declare(strict_types=1);

namespace App\Tests\Catalog\Presentation\Api;

use App\Tests\Support\ApiTestCase;

final class CreateProductControllerTest extends ApiTestCase
{
    public function testItCreatesAProduct(): void
    {
        $data = $this->postJson('/api/products', [
            'name' => 'PlayStation 5',
            'slug' => 'playstation-5',
            'priceAmount' => 49999,
            'currency' => 'EUR',
            'description' => 'Current-generation console.',
        ]);

        $this->assertCreated();

        $this->assertJsonContains([
            'name' => 'PlayStation 5',
            'slug' => 'playstation-5',
            'priceAmount' => 49999,
            'currency' => 'EUR',
            'description' => 'Current-generation console.',
            'isActive' => true,
        ], $data);

        self::assertIsInt($data['id']);
        self::assertNotEmpty($data['createdAt']);
        self::assertNotEmpty($data['updatedAt']);
    }

    public function testItRejectsADuplicateSlug(): void
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
    }
}
