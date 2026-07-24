<?php

declare(strict_types=1);

namespace App\Tests\Catalog\Presentation\Api;

use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;
use App\Tests\Support\ApiTestCase;

final class UpdateProductControllerTest extends ApiTestCase
{
    public function testItUpdatesAProduct(): void
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

        $updated = $this->putJson(sprintf('/api/products/%d', $created['id']), [
            'name' => 'PlayStation 5 Pro',
            'slug' => 'playstation-5-pro',
            'priceAmount' => 79999,
            'currency' => 'USD',
            'description' => 'Upgraded console.',
        ]);

        $this->assertOk();

        $this->assertJsonContains([
            'id' => $created['id'],
            'name' => 'PlayStation 5 Pro',
            'slug' => 'playstation-5-pro',
            'priceAmount' => 79999,
            'currency' => 'USD',
            'description' => 'Upgraded console.',
            'isActive' => true,
        ], $updated);

        // The change is persisted and the creation time is preserved.
        $fetched = $this->getJson(sprintf('/api/products/%d', $created['id']));

        $this->assertOk();

        $this->assertJsonContains([
            'id' => $created['id'],
            'name' => 'PlayStation 5 Pro',
            'slug' => 'playstation-5-pro',
            'priceAmount' => 79999,
            'currency' => 'USD',
            'description' => 'Upgraded console.',
        ], $fetched);

        self::assertSame($created['createdAt'], $fetched['createdAt']);
    }

    public function testItReturnsNotFoundWhenProductDoesNotExist(): void
    {
        $this->client->jsonRequest('PUT', '/api/products/999999', [
            'name' => 'PlayStation 5',
            'slug' => 'playstation-5',
            'priceAmount' => 49999,
            'currency' => 'EUR',
            'description' => 'Current-generation console.',
        ]);

        $this->assertStatusCode(404);
    }

    public function testItRejectsAnInvalidPayload(): void
    {
        $created = $this->postJson('/api/products', [
            'name' => 'PlayStation 5',
            'slug' => 'playstation-5',
            'priceAmount' => 49999,
            'currency' => 'EUR',
            'description' => 'Current-generation console.',
        ]);

        $this->assertCreated();

        $this->client->jsonRequest('PUT', sprintf('/api/products/%d', $created['id']), [
            'name' => '',
            'slug' => 'Not A Slug',
            'priceAmount' => -1,
            'currency' => 'EUR',
        ]);

        $this->assertStatusCode(422);
    }

    public function testItKeepsTheProductInactiveWhenUpdating(): void
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

        $updated = $this->putJson(sprintf('/api/products/%d', $id), [
            'name' => 'Retro Console X',
            'slug' => 'retro-console-x',
            'priceAmount' => 6000,
            'currency' => 'EUR',
            'description' => null,
        ]);

        $this->assertOk();

        $this->assertJsonContains([
            'name' => 'Retro Console X',
            'slug' => 'retro-console-x',
            'isActive' => false,
        ], $updated);
    }

    private function repository(): ProductRepositoryInterface
    {
        $repository = static::getContainer()->get(ProductRepositoryInterface::class);

        self::assertInstanceOf(ProductRepositoryInterface::class, $repository);

        return $repository;
    }
}
