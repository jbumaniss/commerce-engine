<?php

declare(strict_types=1);

namespace App\Tests\Catalog\Presentation\Api;

use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;
use App\Tests\Support\ApiTestCase;

final class ListProductsControllerTest extends ApiTestCase
{
    public function testItReturnsAnEmptyFirstPageWhenNoProductsExist(): void
    {
        $data = $this->getJson('/api/products');

        $this->assertOk();

        self::assertSame([], $data['items']);
        self::assertSame(1, $data['page']);
        self::assertSame(20, $data['perPage']);
        self::assertSame(0, $data['total']);
        self::assertSame(0, $data['totalPages']);
    }

    public function testItReturnsASingleProductWithMetadata(): void
    {
        $this->createProduct('playstation-5', 'PlayStation 5');

        $data = $this->getJson('/api/products');

        $this->assertOk();

        self::assertSame(1, $data['total']);
        self::assertSame(1, $data['totalPages']);

        $items = $data['items'];

        self::assertIsArray($items);
        self::assertCount(1, $items);

        foreach ($items as $item) {
            /* @var array<string, mixed> $item */
            $this->assertJsonContains([
                'name' => 'PlayStation 5',
                'slug' => 'playstation-5',
                'isActive' => true,
            ], $item);

            self::assertIsInt($item['id']);
            self::assertNotEmpty($item['createdAt']);
        }
    }

    public function testItExcludesInactiveProductsFromThePageAndTotal(): void
    {
        $this->createProduct('playstation-5', 'PlayStation 5');

        $inactive = new Product(
            name: 'Retro Console',
            slug: 'retro-console',
            priceAmount: 5000,
            currency: 'EUR',
        );
        $inactive->deactivate();

        $this->repository()->save($inactive);

        $data = $this->getJson('/api/products');

        $this->assertOk();

        self::assertSame(1, $data['total']);

        $items = $data['items'];

        self::assertIsArray($items);
        self::assertCount(1, $items);

        foreach ($items as $item) {
            /* @var array<string, mixed> $item */
            self::assertSame('playstation-5', $item['slug']);
        }
    }

    public function testItReturnsTheFirstPage(): void
    {
        $this->createProduct('product-a');
        $this->createProduct('product-b');
        $this->createProduct('product-c');

        $data = $this->getJson('/api/products?page=1&perPage=2');

        $this->assertOk();

        self::assertSame(1, $data['page']);
        self::assertSame(2, $data['perPage']);
        self::assertSame(3, $data['total']);
        self::assertSame(2, $data['totalPages']);

        $items = $data['items'];

        self::assertIsArray($items);
        self::assertCount(2, $items);

        self::assertSame(['product-a', 'product-b'], $this->slugs($items));
    }

    public function testItReturnsTheSecondPage(): void
    {
        $this->createProduct('product-a');
        $this->createProduct('product-b');
        $this->createProduct('product-c');

        $data = $this->getJson('/api/products?page=2&perPage=2');

        $this->assertOk();

        self::assertSame(2, $data['page']);
        self::assertSame(3, $data['total']);
        self::assertSame(2, $data['totalPages']);

        $items = $data['items'];

        self::assertIsArray($items);
        self::assertCount(1, $items);

        self::assertSame(['product-c'], $this->slugs($items));
    }

    public function testItSupportsACustomPerPage(): void
    {
        $this->createProduct('product-a');
        $this->createProduct('product-b');
        $this->createProduct('product-c');

        $data = $this->getJson('/api/products?perPage=1');

        $this->assertOk();

        self::assertSame(1, $data['perPage']);
        self::assertSame(3, $data['total']);
        self::assertSame(3, $data['totalPages']);

        $items = $data['items'];

        self::assertIsArray($items);
        self::assertCount(1, $items);
    }

    public function testItReturnsAnEmptyPageBeyondTheEnd(): void
    {
        $this->createProduct('playstation-5', 'PlayStation 5');

        $data = $this->getJson('/api/products?page=5&perPage=20');

        $this->assertOk();

        self::assertSame(5, $data['page']);
        self::assertSame(1, $data['total']);
        self::assertSame(1, $data['totalPages']);
        self::assertSame([], $data['items']);
    }

    public function testItRejectsANonPositivePage(): void
    {
        $this->client->request('GET', '/api/products?page=0');

        $this->assertStatusCode(422);
    }

    public function testItRejectsANonPositivePerPage(): void
    {
        $this->client->request('GET', '/api/products?perPage=0');

        $this->assertStatusCode(422);
    }

    public function testItRejectsAPerPageAboveTheMaximum(): void
    {
        $this->client->request('GET', '/api/products?perPage=101');

        $this->assertStatusCode(422);
    }

    public function testItExposesAnEtagForTheList(): void
    {
        $this->createProduct('playstation-5', 'PlayStation 5');

        $this->client->request('GET', '/api/products');

        $this->assertOk();

        self::assertNotNull($this->client->getResponse()->getEtag());
    }

    public function testItReturnsNotModifiedWhenTheListIsUnchanged(): void
    {
        $this->createProduct('playstation-5', 'PlayStation 5');

        $this->client->request('GET', '/api/products');

        $this->assertOk();

        $etag = $this->client->getResponse()->getEtag();

        self::assertNotNull($etag);

        $this->client->request('GET', '/api/products', server: ['HTTP_IF_NONE_MATCH' => $etag]);

        $this->assertStatusCode(304);
        self::assertEmpty($this->client->getResponse()->getContent());
    }

    public function testTheListEtagChangesWhenAProductIsAdded(): void
    {
        $this->createProduct('playstation-5', 'PlayStation 5');

        $this->client->request('GET', '/api/products');

        $staleEtag = $this->client->getResponse()->getEtag();

        self::assertNotNull($staleEtag);

        $this->createProduct('xbox-series-x', 'Xbox Series X');

        $this->client->request('GET', '/api/products', server: ['HTTP_IF_NONE_MATCH' => $staleEtag]);

        $this->assertOk();
        self::assertNotSame($staleEtag, $this->client->getResponse()->getEtag());
    }

    private function createProduct(string $slug, ?string $name = null): void
    {
        $this->postJson('/api/products', [
            'name' => $name ?? ucfirst($slug),
            'slug' => $slug,
            'priceAmount' => 1000,
            'currency' => 'EUR',
            'description' => null,
        ]);

        $this->assertCreated();
    }

    /**
     * @param array<mixed, mixed> $items
     *
     * @return list<mixed>
     */
    private function slugs(array $items): array
    {
        $slugs = [];

        foreach ($items as $item) {
            /* @var array<string, mixed> $item */
            $slugs[] = $item['slug'];
        }

        return $slugs;
    }

    private function repository(): ProductRepositoryInterface
    {
        $repository = static::getContainer()->get(ProductRepositoryInterface::class);

        self::assertInstanceOf(ProductRepositoryInterface::class, $repository);

        return $repository;
    }
}
