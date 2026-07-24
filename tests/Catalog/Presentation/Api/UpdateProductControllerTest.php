<?php

declare(strict_types=1);

namespace App\Tests\Catalog\Presentation\Api;

use App\Catalog\Application\Exception\ProductWasConcurrentlyModified;
use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;
use App\Tests\Support\ApiTestCase;
use Doctrine\DBAL\Connection;

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

    public function testItRejectsAnotherProductsSlug(): void
    {
        $this->postJson('/api/products', [
            'name' => 'PlayStation 5',
            'slug' => 'playstation-5',
            'priceAmount' => 49999,
            'currency' => 'EUR',
            'description' => 'Current-generation console.',
        ]);

        $this->assertCreated();

        $other = $this->postJson('/api/products', [
            'name' => 'Xbox Series X',
            'slug' => 'xbox-series-x',
            'priceAmount' => 47999,
            'currency' => 'EUR',
            'description' => 'Current-generation console.',
        ]);

        $this->assertCreated();

        $this->client->jsonRequest('PUT', sprintf('/api/products/%d', $other['id']), [
            'name' => 'Xbox Series X',
            'slug' => 'playstation-5',
            'priceAmount' => 47999,
            'currency' => 'EUR',
            'description' => 'Current-generation console.',
        ]);

        $this->assertStatusCode(409);
    }

    public function testItAllowsUpdatingAProductWithItsOwnSlug(): void
    {
        $created = $this->postJson('/api/products', [
            'name' => 'PlayStation 5',
            'slug' => 'playstation-5',
            'priceAmount' => 49999,
            'currency' => 'EUR',
            'description' => 'Current-generation console.',
        ]);

        $this->assertCreated();

        $updated = $this->putJson(sprintf('/api/products/%d', $created['id']), [
            'name' => 'PlayStation 5 Slim',
            'slug' => 'playstation-5',
            'priceAmount' => 44999,
            'currency' => 'EUR',
            'description' => 'Slimmer revision.',
        ]);

        $this->assertOk();

        $this->assertJsonContains([
            'id' => $created['id'],
            'name' => 'PlayStation 5 Slim',
            'slug' => 'playstation-5',
            'priceAmount' => 44999,
        ], $updated);
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

    public function testItIncrementsTheStoredVersionWhenUpdated(): void
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
        self::assertSame(1, $this->storedVersion($created['id']));

        $this->putJson(sprintf('/api/products/%d', $created['id']), [
            'name' => 'PlayStation 5 Pro',
            'slug' => 'playstation-5-pro',
            'priceAmount' => 79999,
            'currency' => 'USD',
            'description' => 'Upgraded console.',
        ]);

        $this->assertOk();

        // Doctrine advances the optimistic-lock version on every successful update.
        self::assertSame(2, $this->storedVersion($created['id']));
    }

    public function testAStaleUpdateIsRejectedAsAConflict(): void
    {
        $product = new Product(
            name: 'PlayStation 5',
            slug: 'playstation-5',
            priceAmount: 49999,
            currency: 'EUR',
            description: 'Current-generation console.',
        );
        $this->repository()->save($product);

        $id = $product->id();

        self::assertIsInt($id);
        self::assertSame(1, $product->version());

        // A concurrent writer commits first, advancing the stored version out from under
        // the entity still held in this unit of work.
        $this->connection()->executeStatement(
            'UPDATE catalog_products SET version = version + 1 WHERE id = :id',
            ['id' => $id],
        );

        $product->update(
            name: 'PlayStation 5 Pro',
            slug: 'playstation-5-pro',
            priceAmount: 79999,
            currency: 'USD',
            description: 'Upgraded console.',
        );

        // Doctrine's optimistic-lock check fails on flush and is translated into the
        // conflict exception, which the RFC 7807 listener renders as HTTP 409
        // (see ApiErrorResponseTest::testConflictReturnsProblemJson).
        $this->expectException(ProductWasConcurrentlyModified::class);

        $this->repository()->save($product);
    }

    private function repository(): ProductRepositoryInterface
    {
        $repository = static::getContainer()->get(ProductRepositoryInterface::class);

        self::assertInstanceOf(ProductRepositoryInterface::class, $repository);

        return $repository;
    }

    private function connection(): Connection
    {
        $connection = static::getContainer()->get(Connection::class);

        self::assertInstanceOf(Connection::class, $connection);

        return $connection;
    }

    private function storedVersion(int $id): int
    {
        $version = $this->connection()->fetchOne(
            'SELECT version FROM catalog_products WHERE id = :id',
            ['id' => $id],
        );

        self::assertIsNumeric($version);

        return (int) $version;
    }
}
