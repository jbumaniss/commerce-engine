<?php

declare(strict_types=1);

namespace App\Tests\Catalog\Presentation\Api;

use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;
use App\Tests\Support\ApiTestCase;

final class ListProductsControllerTest extends ApiTestCase
{
    public function testItReturnsAnEmptyArrayWhenNoProductsExist(): void
    {
        $products = $this->getJson('/api/products');

        $this->assertOk();

        self::assertSame([], $products);
    }

    public function testItReturnsASingleProduct(): void
    {
        $this->postJson('/api/products', [
            'name' => 'PlayStation 5',
            'slug' => 'playstation-5',
            'priceAmount' => 49999,
            'currency' => 'EUR',
            'description' => 'Current-generation console.',
        ]);

        $this->assertCreated();

        $products = $this->getJson('/api/products');

        $this->assertOk();

        self::assertCount(1, $products);

        foreach ($products as $product) {
            /* @var array<string, mixed> $product */
            $this->assertJsonContains([
                'name' => 'PlayStation 5',
                'slug' => 'playstation-5',
                'priceAmount' => 49999,
                'currency' => 'EUR',
                'description' => 'Current-generation console.',
                'isActive' => true,
            ], $product);

            self::assertIsInt($product['id']);
            self::assertNotEmpty($product['createdAt']);
            self::assertNotEmpty($product['updatedAt']);
        }
    }

    public function testItReturnsMultipleProducts(): void
    {
        $this->postJson('/api/products', [
            'name' => 'PlayStation 5',
            'slug' => 'playstation-5',
            'priceAmount' => 49999,
            'currency' => 'EUR',
            'description' => 'Current-generation console.',
        ]);

        $this->postJson('/api/products', [
            'name' => 'Xbox Series X',
            'slug' => 'xbox-series-x',
            'priceAmount' => 47999,
            'currency' => 'EUR',
            'description' => 'Current-generation console.',
        ]);

        $products = $this->getJson('/api/products');

        $this->assertOk();

        self::assertCount(2, $products);

        $slugs = [];

        foreach ($products as $product) {
            /* @var array<string, mixed> $product */
            $slugs[] = $product['slug'];
        }

        self::assertContains('playstation-5', $slugs);
        self::assertContains('xbox-series-x', $slugs);
    }

    public function testItExcludesInactiveProducts(): void
    {
        $this->postJson('/api/products', [
            'name' => 'PlayStation 5',
            'slug' => 'playstation-5',
            'priceAmount' => 49999,
            'currency' => 'EUR',
            'description' => 'Current-generation console.',
        ]);

        $this->assertCreated();

        $inactive = new Product(
            name: 'Retro Console',
            slug: 'retro-console',
            priceAmount: 5000,
            currency: 'EUR',
        );
        $inactive->deactivate();

        $this->repository()->save($inactive);

        $products = $this->getJson('/api/products');

        $this->assertOk();

        self::assertCount(1, $products);

        foreach ($products as $product) {
            /* @var array<string, mixed> $product */
            self::assertSame('playstation-5', $product['slug']);
            self::assertTrue($product['isActive']);
        }
    }

    private function repository(): ProductRepositoryInterface
    {
        $repository = static::getContainer()->get(ProductRepositoryInterface::class);

        self::assertInstanceOf(ProductRepositoryInterface::class, $repository);

        return $repository;
    }
}
