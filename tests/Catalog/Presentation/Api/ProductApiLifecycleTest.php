<?php

declare(strict_types=1);

namespace App\Tests\Catalog\Presentation\Api;

use App\Tests\Support\ApiTestCase;

/**
 * End-to-end regression scenario walking a single product across the whole API surface,
 * guarding that the endpoints work together (not just in isolation).
 */
final class ProductApiLifecycleTest extends ApiTestCase
{
    public function testAProductMovesThroughItsFullLifecycle(): void
    {
        // Create.
        $created = $this->postJson('/api/products', [
            'name' => 'PlayStation 5',
            'slug' => 'playstation-5',
            'priceAmount' => 49999,
            'currency' => 'EUR',
            'description' => 'Current-generation console.',
        ]);

        $this->assertCreated();

        $id = $created['id'];

        self::assertIsInt($id);

        $uri = sprintf('/api/products/%d', $id);

        // Reading it back yields the exact same representation.
        $fetched = $this->getJson($uri);

        $this->assertOk();
        self::assertSame($created, $fetched);

        // It appears in the active listing.
        $list = $this->getJson('/api/products');

        $this->assertOk();
        self::assertIsArray($list['items']);
        self::assertContains($id, array_column($list['items'], 'id'));

        // Full-replace update keeps the id and creation time.
        $updated = $this->putJson($uri, [
            'name' => 'PlayStation 5 Pro',
            'slug' => 'playstation-5-pro',
            'priceAmount' => 79999,
            'currency' => 'USD',
            'description' => 'Upgraded console.',
        ]);

        $this->assertOk();
        $this->assertJsonContains([
            'id' => $id,
            'name' => 'PlayStation 5 Pro',
            'slug' => 'playstation-5-pro',
            'priceAmount' => 79999,
            'currency' => 'USD',
        ], $updated);
        self::assertSame($created['createdAt'], $updated['createdAt']);

        // Deactivate then reactivate.
        $deactivated = $this->postJson($uri.'/deactivate', []);

        $this->assertOk();
        self::assertFalse($deactivated['isActive']);

        $activated = $this->postJson($uri.'/activate', []);

        $this->assertOk();
        self::assertTrue($activated['isActive']);

        // Delete returns an empty 204.
        $this->client->request('DELETE', $uri);

        $this->assertStatusCode(204);
        self::assertEmpty($this->client->getResponse()->getContent());

        // The product is gone.
        $this->client->request('GET', $uri);

        $this->assertStatusCode(404);
    }
}
