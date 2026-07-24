<?php

declare(strict_types=1);

namespace App\Tests\Catalog\Presentation\Api;

use App\Tests\Support\ApiTestCase;

final class GetProductControllerTest extends ApiTestCase
{
    public function testItReturnsAProduct(): void
    {
        $created = $this->postJson('/api/products', [
            'name' => 'PlayStation 5',
            'slug' => 'playstation-5',
            'priceAmount' => 49999,
            'currency' => 'EUR',
            'description' => 'Current-generation console.',
        ]);

        $this->assertCreated();

        $data = $this->getJson(sprintf('/api/products/%d', $created['id']));

        $this->assertOk();

        $this->assertJsonContains([
            'id' => $created['id'],
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

    public function testItReturnsNotFoundWhenProductDoesNotExist(): void
    {
        $this->client->request('GET', '/api/products/999999');

        $this->assertStatusCode(404);
    }
}
