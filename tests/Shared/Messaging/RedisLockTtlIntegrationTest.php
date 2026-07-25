<?php

declare(strict_types=1);

namespace App\Tests\Shared\Messaging;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\SharedLockInterface;
use Symfony\Component\Lock\Store\RedisStore;
use Symfony\Component\Lock\Store\StoreFactory;

/**
 * Integration test against the real Redis service (docker compose's "redis", the same instance
 * REDIS_URL points the lock/cache infrastructure at in dev and CI).
 *
 * DeduplicatingMiddlewareTest's stale-claim case proves the *code path* (defer, then succeed on
 * redelivery) quickly and deterministically by manually releasing an InMemoryStore lock — that
 * store never expires a TTL, so it cannot prove expiry itself. This test proves the underlying
 * property claim recovery actually relies on in production: a Redis-backed lock disappears on
 * its own once its TTL elapses, with no explicit release. It uses a short TTL — not
 * DeduplicatingMiddleware's real 300s CLAIM_TTL — to stay fast, and exercises the same
 * LockFactory/Lock abstraction the middleware uses, not the lower-level store API directly.
 *
 * Marked #[Group('integration')]: unlike the rest of the suite (which runs against in-memory/
 * flock stand-ins so it never depends on a broker), this test requires a reachable Redis. It is
 * still part of the default run because Redis is baseline project infrastructure — a required,
 * health-checked compose service already present in dev and in CI (which brings up compose.yaml
 * unmodified) — the same way the existing suite already depends on a real Postgres via the DAMA
 * bundle without being separately marked.
 */
#[Group('integration')]
final class RedisLockTtlIntegrationTest extends TestCase
{
    private const float SHORT_TTL_SECONDS = 0.5;

    private LockFactory $factory;
    private string $resource;
    private ?SharedLockInterface $activeLock = null;

    protected function setUp(): void
    {
        $redisUrl = (string) (getenv('REDIS_URL') ?: '');

        if ('' === $redisUrl) {
            self::markTestSkipped('REDIS_URL is not set in this environment.');
        }

        try {
            $store = StoreFactory::createStore($redisUrl);
        } catch (\Throwable $exception) {
            self::markTestSkipped(sprintf('Redis is not reachable at "%s": %s', $redisUrl, $exception->getMessage()));
        }

        self::assertInstanceOf(RedisStore::class, $store);

        $this->factory = new LockFactory($store);
        // A unique resource per run avoids collisions with a concurrent test run or a leftover key.
        $this->resource = 'ce025_ttl_verify_'.bin2hex(random_bytes(8));
    }

    protected function tearDown(): void
    {
        // Best-effort cleanup: if the lock already expired or was never acquired, this is a no-op.
        try {
            if (null !== $this->activeLock && $this->activeLock->isAcquired()) {
                $this->activeLock->release();
            }
        } catch (\Throwable) {
        }
    }

    public function testARedisLockExpiresOnItsOwnAfterItsTtlElapsesWithNoExplicitRelease(): void
    {
        // 1. + 2. Create and acquire a lock with a very short TTL. autoRelease is disabled so
        // nothing but Redis's own key expiry can free it — proving TTL expiry, not our release.
        $lockA = $this->factory->createLock($this->resource, self::SHORT_TTL_SECONDS, autoRelease: false);

        self::assertTrue($lockA->acquire(), 'The first claim on an unclaimed resource must succeed.');

        $this->activeLock = $lockA;

        // 3. A second, independent lock instance for the same resource cannot acquire it while
        // the first is still active — the atomic claim DeduplicatingMiddleware relies on.
        $lockB = $this->factory->createLock($this->resource, self::SHORT_TTL_SECONDS, autoRelease: false);

        self::assertFalse($lockB->acquire(), 'A second instance must not be able to claim an active lock.');

        // 4. Wait past the TTL. lockA is never released explicitly: only Redis's own expiry can
        // free this claim, exactly like a crashed worker's claim in production.
        usleep((int) (self::SHORT_TTL_SECONDS * 1_000_000) + 200_000);

        // 5. A fresh instance can now acquire the same resource — stale-claim recovery.
        $lockC = $this->factory->createLock($this->resource, self::SHORT_TTL_SECONDS, autoRelease: false);

        self::assertTrue($lockC->acquire(), 'A lock must become claimable again once its TTL has elapsed.');

        $this->activeLock = $lockC;

        // 6. Cleanup: release the claim this test holds (tearDown covers the case where an
        // assertion above fails before reaching this point).
        $lockC->release();

        self::assertFalse($lockC->isAcquired());

        $this->activeLock = null;
    }
}
