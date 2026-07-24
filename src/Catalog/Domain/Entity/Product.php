<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'catalog_products')]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    // @phpstan-ignore property.unusedType (Doctrine assigns the generated ID after persistence.)
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 255, unique: true)]
    private string $slug;

    #[ORM\Column]
    private int $priceAmount;

    #[ORM\Column(length: 3)]
    private string $currency;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description;

    #[ORM\Column]
    private bool $isActive;

    #[ORM\Column]
    private readonly \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        string $name,
        string $slug,
        int $priceAmount,
        string $currency,
        ?string $description = null,
    ) {
        $now = new \DateTimeImmutable();

        $this->name = self::normalizeName($name);
        $this->slug = self::normalizeSlug($slug);
        $this->priceAmount = self::validatePriceAmount($priceAmount);
        $this->currency = self::normalizeCurrency($currency);
        $this->description = self::normalizeDescription($description);
        $this->isActive = true;
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function priceAmount(): int
    {
        return $this->priceAmount;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function update(
        string $name,
        string $slug,
        int $priceAmount,
        string $currency,
        ?string $description = null,
    ): void {
        $this->name = self::normalizeName($name);
        $this->slug = self::normalizeSlug($slug);
        $this->priceAmount = self::validatePriceAmount($priceAmount);
        $this->currency = self::normalizeCurrency($currency);
        $this->description = self::normalizeDescription($description);
        $this->touch();
    }

    public function activate(): void
    {
        if ($this->isActive) {
            return;
        }

        $this->isActive = true;
        $this->touch();
    }

    public function deactivate(): void
    {
        if (!$this->isActive) {
            return;
        }

        $this->isActive = false;
        $this->touch();
    }

    private static function normalizeName(string $name): string
    {
        $name = trim($name);

        if ('' === $name) {
            throw new \InvalidArgumentException('Product name cannot be empty.');
        }

        return $name;
    }

    private static function normalizeSlug(string $slug): string
    {
        $slug = trim($slug);

        if ('' === $slug) {
            throw new \InvalidArgumentException('Product slug cannot be empty.');
        }

        return $slug;
    }

    private static function validatePriceAmount(int $priceAmount): int
    {
        if (0 > $priceAmount) {
            throw new \InvalidArgumentException('Product price cannot be negative.');
        }

        return $priceAmount;
    }

    private static function normalizeCurrency(string $currency): string
    {
        $currency = strtoupper(trim($currency));

        if (3 !== strlen($currency)) {
            throw new \InvalidArgumentException('Product currency must use a three-letter code.');
        }

        return $currency;
    }

    private static function normalizeDescription(?string $description): ?string
    {
        if (null === $description) {
            return null;
        }

        $description = trim($description);

        return '' === $description ? null : $description;
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
