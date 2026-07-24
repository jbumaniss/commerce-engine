<?php

declare(strict_types=1);

namespace App\Tests\Catalog\Domain\Entity;

use App\Catalog\Domain\Entity\Product;
use PHPUnit\Framework\TestCase;

final class ProductTest extends TestCase
{
    public function testItCreatesAnActiveProduct(): void
    {
        $product = $this->createProduct();

        self::assertTrue($product->isActive());
    }

    public function testItExposesProductData(): void
    {
        $product = new Product(
            name: 'PlayStation 5',
            slug: 'playstation-5',
            priceAmount: 49999,
            currency: 'EUR',
            description: 'Current-generation console.',
        );

        self::assertSame('PlayStation 5', $product->name());
        self::assertSame('playstation-5', $product->slug());
        self::assertSame(49999, $product->priceAmount());
        self::assertSame('EUR', $product->currency());
        self::assertSame('Current-generation console.', $product->description());
    }

    public function testItTrimsTheProductName(): void
    {
        $product = $this->createProduct(name: '  PlayStation 5  ');

        self::assertSame('PlayStation 5', $product->name());
    }

    public function testItTrimsTheProductSlug(): void
    {
        $product = $this->createProduct(slug: '  playstation-5  ');

        self::assertSame('playstation-5', $product->slug());
    }

    public function testItNormalizesCurrencyToUppercase(): void
    {
        $product = $this->createProduct(currency: 'eur');

        self::assertSame('EUR', $product->currency());
    }

    public function testItTrimsTheCurrency(): void
    {
        $product = $this->createProduct(currency: ' eur ');

        self::assertSame('EUR', $product->currency());
    }

    public function testItNormalizesAnEmptyDescriptionToNull(): void
    {
        $product = $this->createProduct(description: '   ');

        self::assertNull($product->description());
    }

    public function testItTrimsTheDescription(): void
    {
        $product = $this->createProduct(description: '  Console  ');

        self::assertSame('Console', $product->description());
    }

    public function testItDeactivatesAnActiveProduct(): void
    {
        $product = $this->createProduct();

        $product->deactivate();

        self::assertFalse($product->isActive());
    }

    public function testItActivatesAnInactiveProduct(): void
    {
        $product = $this->createProduct();
        $product->deactivate();

        $product->activate();

        self::assertTrue($product->isActive());
    }

    public function testDeactivatingAnInactiveProductIsIdempotent(): void
    {
        $product = $this->createProduct();
        $product->deactivate();

        $updatedAt = $product->updatedAt();

        $product->deactivate();

        self::assertFalse($product->isActive());
        self::assertSame($updatedAt, $product->updatedAt());
    }

    public function testActivatingAnActiveProductIsIdempotent(): void
    {
        $product = $this->createProduct();
        $updatedAt = $product->updatedAt();

        $product->activate();

        self::assertTrue($product->isActive());
        self::assertSame($updatedAt, $product->updatedAt());
    }

    public function testItRejectsAnEmptyName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Product name cannot be empty.');

        $this->createProduct(name: '   ');
    }

    public function testItRejectsAnEmptySlug(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Product slug cannot be empty.');

        $this->createProduct(slug: '   ');
    }

    public function testItRejectsANegativePrice(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Product price cannot be negative.');

        $this->createProduct(priceAmount: -1);
    }

    public function testItAllowsAZeroPrice(): void
    {
        $product = $this->createProduct(priceAmount: 0);

        self::assertSame(0, $product->priceAmount());
    }

    public function testItRejectsACurrencyShorterThanThreeCharacters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Product currency must use a three-letter code.');

        $this->createProduct(currency: 'EU');
    }

    public function testItRejectsACurrencyLongerThanThreeCharacters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Product currency must use a three-letter code.');

        $this->createProduct(currency: 'EURO');
    }

    private function createProduct(
        string $name = 'PlayStation 5',
        string $slug = 'playstation-5',
        int $priceAmount = 49999,
        string $currency = 'EUR',
        ?string $description = null,
    ): Product {
        return new Product(
            name: $name,
            slug: $slug,
            priceAmount: $priceAmount,
            currency: $currency,
            description: $description,
        );
    }
}
