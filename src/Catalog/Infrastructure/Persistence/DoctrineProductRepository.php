<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Persistence;

use App\Catalog\Application\Exception\ProductWasConcurrentlyModified;
use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;

final readonly class DoctrineProductRepository implements ProductRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Product $product): void
    {
        $this->entityManager->persist($product);

        try {
            $this->entityManager->flush();
        } catch (OptimisticLockException) {
            throw ProductWasConcurrentlyModified::create();
        }
    }

    public function remove(Product $product): void
    {
        $this->entityManager->remove($product);
        $this->entityManager->flush();
    }

    public function findById(int $id): ?Product
    {
        return $this->entityManager->find(Product::class, $id);
    }

    public function findBySlug(string $slug): ?Product
    {
        return $this->entityManager
            ->getRepository(Product::class)
            ->findOneBy([
                'slug' => $slug,
            ]);
    }

    public function findActive(int $limit, int $offset): array
    {
        return $this->entityManager
            ->getRepository(Product::class)
            ->findBy(
                ['isActive' => true],
                ['id' => 'ASC'],
                $limit,
                $offset,
            );
    }

    public function countActive(): int
    {
        return $this->entityManager
            ->getRepository(Product::class)
            ->count(['isActive' => true]);
    }
}
