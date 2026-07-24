<?php

declare(strict_types=1);

namespace App\Tests\Catalog\Presentation\Api;

use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;
use App\Tests\Support\ApiTestCase;

final class ActivateProductControllerTest extends ApiTestCase
{
    public function testItActivatesAnInactiveProduct(): void
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

        $this->client->request('POST', sprintf('/api/products/%d/activate', $id));

        $this->assertOk();

        $this->assertJsonContains([
            'id' => $id,
            'isActive' => true,
        ], $this->json());

        // The change is persisted.
        $fetched = $this->getJson(sprintf('/api/products/%d', $id));

        $this->assertOk();

        $this->assertJsonContains(['isActive' => true], $fetched);
    }

    public function testItIsIdempotentWhenTheProductIsAlreadyActive(): void
    {
        $created = $this->postJson('/api/products', [
            'name' => 'PlayStation 5',
            'slug' => 'playstation-5',
            'priceAmount' => 49999,
            'currency' => 'EUR',
            'description' => 'Current-generation console.',
        ]);

        $this->assertCreated();

        $this->client->request('POST', sprintf('/api/products/%d/activate', $created['id']));

        $this->assertOk();

        $this->assertJsonContains([
            'id' => $created['id'],
            'isActive' => true,
        ], $this->json());
    }

    public function testItReturnsNotFoundWhenProductDoesNotExist(): void
    {
        $this->client->request('POST', '/api/products/999999/activate');

        $this->assertStatusCode(404);
    }

    private function repository(): ProductRepositoryInterface
    {
        $repository = static::getContainer()->get(ProductRepositoryInterface::class);

        self::assertInstanceOf(ProductRepositoryInterface::class, $repository);

        return $repository;
    }
}
