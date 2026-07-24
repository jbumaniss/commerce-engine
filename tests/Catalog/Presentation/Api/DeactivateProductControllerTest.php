<?php

declare(strict_types=1);

namespace App\Tests\Catalog\Presentation\Api;

use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;
use App\Tests\Support\ApiTestCase;

final class DeactivateProductControllerTest extends ApiTestCase
{
    public function testItDeactivatesAnActiveProduct(): void
    {
        $created = $this->postJson('/api/products', [
            'name' => 'PlayStation 5',
            'slug' => 'playstation-5',
            'priceAmount' => 49999,
            'currency' => 'EUR',
            'description' => 'Current-generation console.',
        ]);

        $this->assertCreated();

        $this->client->request('POST', sprintf('/api/products/%d/deactivate', $created['id']));

        $this->assertOk();

        $this->assertJsonContains([
            'id' => $created['id'],
            'isActive' => false,
        ], $this->json());

        // The change is persisted.
        $fetched = $this->getJson(sprintf('/api/products/%d', $created['id']));

        $this->assertOk();

        $this->assertJsonContains(['isActive' => false], $fetched);
    }

    public function testItIsIdempotentWhenTheProductIsAlreadyInactive(): void
    {
        $product = new Product(
            name: 'PlayStation 5',
            slug: 'playstation-5',
            priceAmount: 49999,
            currency: 'EUR',
        );
        $product->deactivate();

        $this->repository()->save($product);

        $id = $product->id();

        self::assertIsInt($id);

        $this->client->request('POST', sprintf('/api/products/%d/deactivate', $id));

        $this->assertOk();

        $this->assertJsonContains([
            'id' => $id,
            'isActive' => false,
        ], $this->json());
    }

    public function testItReturnsNotFoundWhenProductDoesNotExist(): void
    {
        $this->client->request('POST', '/api/products/999999/deactivate');

        $this->assertStatusCode(404);
    }

    private function repository(): ProductRepositoryInterface
    {
        $repository = static::getContainer()->get(ProductRepositoryInterface::class);

        self::assertInstanceOf(ProductRepositoryInterface::class, $repository);

        return $repository;
    }
}
