<?php

declare(strict_types=1);

namespace App\Tests\Catalog\Presentation\Api;

use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;
use App\Tests\Support\ApiTestCase;

final class DeleteProductControllerTest extends ApiTestCase
{
    public function testItDeletesAProduct(): void
    {
        $created = $this->postJson('/api/products', [
            'name' => 'PlayStation 5',
            'slug' => 'playstation-5',
            'priceAmount' => 49999,
            'currency' => 'EUR',
            'description' => 'Current-generation console.',
        ]);

        $this->assertCreated();

        $this->client->request('DELETE', sprintf('/api/products/%d', $created['id']));

        $this->assertStatusCode(204);

        self::assertEmpty($this->client->getResponse()->getContent());

        // The product is really gone.
        $this->client->request('GET', sprintf('/api/products/%d', $created['id']));

        $this->assertStatusCode(404);
    }

    public function testItDeletesAnInactiveProduct(): void
    {
        $product = new Product(
            name: 'Retro Console',
            slug: 'retro-console',
            priceAmount: 5000,
            currency: 'EUR',
        );
        $product->deactivate();

        $this->repository()->save($product);

        $id = $product->id();

        self::assertIsInt($id);

        $this->client->request('DELETE', sprintf('/api/products/%d', $id));

        $this->assertStatusCode(204);

        $this->client->request('GET', sprintf('/api/products/%d', $id));

        $this->assertStatusCode(404);
    }

    public function testItReturnsNotFoundWhenProductDoesNotExist(): void
    {
        $this->client->request('DELETE', '/api/products/999999');

        $this->assertStatusCode(404);
    }

    public function testItReturnsNotFoundWhenDeletingAnAlreadyDeletedProduct(): void
    {
        $created = $this->postJson('/api/products', [
            'name' => 'PlayStation 5',
            'slug' => 'playstation-5',
            'priceAmount' => 49999,
            'currency' => 'EUR',
            'description' => 'Current-generation console.',
        ]);

        $this->assertCreated();

        $this->client->request('DELETE', sprintf('/api/products/%d', $created['id']));

        $this->assertStatusCode(204);

        // A hard delete leaves no tombstone, so a repeated delete is indistinguishable
        // from deleting a product that never existed: 404.
        $this->client->request('DELETE', sprintf('/api/products/%d', $created['id']));

        $this->assertStatusCode(404);
    }

    private function repository(): ProductRepositoryInterface
    {
        $repository = static::getContainer()->get(ProductRepositoryInterface::class);

        self::assertInstanceOf(ProductRepositoryInterface::class, $repository);

        return $repository;
    }
}
