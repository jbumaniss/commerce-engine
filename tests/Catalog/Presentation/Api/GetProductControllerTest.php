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

    public function testItExposesCacheValidators(): void
    {
        $id = $this->createProduct();

        $this->client->request('GET', sprintf('/api/products/%d', $id));

        $this->assertOk();

        $response = $this->client->getResponse();

        self::assertNotNull($response->getEtag());
        self::assertNotNull($response->headers->get('Last-Modified'));
    }

    public function testItReturnsNotModifiedWhenTheEtagStillMatches(): void
    {
        $id = $this->createProduct();
        $uri = sprintf('/api/products/%d', $id);

        $this->client->request('GET', $uri);

        $this->assertOk();

        $etag = $this->client->getResponse()->getEtag();

        self::assertNotNull($etag);

        $this->client->request('GET', $uri, server: ['HTTP_IF_NONE_MATCH' => $etag]);

        $this->assertStatusCode(304);
        self::assertEmpty($this->client->getResponse()->getContent());
    }

    public function testAWriteInvalidatesTheCachedValidator(): void
    {
        $id = $this->createProduct();
        $uri = sprintf('/api/products/%d', $id);

        $this->client->request('GET', $uri);

        $staleEtag = $this->client->getResponse()->getEtag();

        self::assertNotNull($staleEtag);

        $this->putJson($uri, [
            'name' => 'PlayStation 5 Pro',
            'slug' => 'playstation-5-pro',
            'priceAmount' => 79999,
            'currency' => 'USD',
            'description' => 'Upgraded console.',
        ]);

        $this->assertOk();

        // The previously cached validator no longer matches: a full 200 with a fresh ETag.
        $this->client->request('GET', $uri, server: ['HTTP_IF_NONE_MATCH' => $staleEtag]);

        $this->assertOk();
        self::assertNotSame($staleEtag, $this->client->getResponse()->getEtag());
    }

    private function createProduct(): int
    {
        $created = $this->postJson('/api/products', [
            'name' => 'PlayStation 5',
            'slug' => 'playstation-5',
            'priceAmount' => 49999,
            'currency' => 'EUR',
            'description' => 'Current-generation console.',
        ]);

        $this->assertCreated();

        self::assertIsInt($created['id']);

        return $created['id'];
    }
}
