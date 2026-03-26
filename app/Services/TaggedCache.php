<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Cache\Repository;

/**
 * Tagged cache service for tenant-aware cache invalidation.
 *
 * Provides a centralized way to cache data with automatic tag-based
 * invalidation when entities change. Tags are organized by tenant (organization)
 * and entity type for granular cache management.
 */
final class TaggedCache
{
    private const DEFAULT_TTL = 3600; // 1 hour

    private const TAG_TENANT_PREFIX = 'tenant:';

    private const TAG_ENTITY_PREFIX = 'entity:';

    private const TAG_FEATURE_PREFIX = 'feature:';

    public function __construct(
        private readonly CacheManager $cache,
    ) {}

    /**
     * Get cached data or execute callback to store it.
     *
     * @template T
     *
     * @param  string  $key  Cache key
     * @param  array<string>  $tags  Cache tags for invalidation
     * @param  callable(): T  $callback  Data generator
     * @param  int|null  $ttl  Time to live in seconds (null = forever)
     * @return T
     */
    public function remember(string $key, array $tags, callable $callback, ?int $ttl = null): mixed
    {
        return $this->repository()
            ->tags($this->normalizeTags($tags))
            ->remember($key, $ttl ?? self::DEFAULT_TTL, $callback);
    }

    /**
     * Store data in cache with tags.
     *
     * @param  string  $key  Cache key
     * @param  array<string>  $tags  Cache tags for invalidation
     * @param  mixed  $value  Data to cache
     * @param  int|null  $ttl  Time to live in seconds (null = forever)
     */
    public function put(string $key, array $tags, mixed $value, ?int $ttl = null): bool
    {
        return $this->repository()
            ->tags($this->normalizeTags($tags))
            ->put($key, $value, $ttl ?? self::DEFAULT_TTL);
    }

    /**
     * Get a value stored under the given tag set (must match the tags used in put / remember).
     *
     * @param  array<string>  $tags
     */
    public function get(string $key, array $tags): mixed
    {
        return $this->repository()
            ->tags($this->normalizeTags($tags))
            ->get($key);
    }

    /**
     * @param  array<string>  $tags
     */
    public function has(string $key, array $tags): bool
    {
        return $this->repository()
            ->tags($this->normalizeTags($tags))
            ->has($key);
    }

    /**
     * @param  array<string>  $tags
     */
    public function forget(string $key, array $tags): bool
    {
        return $this->repository()
            ->tags($this->normalizeTags($tags))
            ->forget($key);
    }

    /**
     * Invalidate all entries that were stored with any of the given tags.
     *
     * @param  array<string>  $tags  Tags to invalidate
     */
    public function flushTags(array $tags): bool
    {
        $normalized = $this->normalizeTags($tags);

        if ($normalized === []) {
            return false;
        }

        return $this->repository()->tags($normalized)->flush();
    }

    /**
     * Invalidate all cache for a specific tenant.
     */
    public function flushTenant(int $organizationId): bool
    {
        return $this->flushTags($this->tenantTags($organizationId));
    }

    /**
     * Invalidate entries tagged with `entity:{type}:{id}` only (not the whole `entity:{type}` bucket).
     */
    public function flushEntity(string $entityType, int|string $entityId): bool
    {
        return $this->flushTags([
            self::TAG_ENTITY_PREFIX.$entityType.':'.$entityId,
        ]);
    }

    /**
     * Invalidate feature cache for a tenant.
     */
    public function flushFeatures(int $organizationId): bool
    {
        return $this->flushTags([
            $this->featureTag($organizationId),
        ]);
    }

    /**
     * Generate tenant-specific tags.
     *
     * @return array<string>
     */
    public function tenantTags(int $organizationId): array
    {
        return [
            self::TAG_TENANT_PREFIX.$organizationId,
        ];
    }

    /**
     * Generate entity-specific tags.
     *
     * @return array<string>
     */
    public function entityTags(string $entityType, int|string $entityId): array
    {
        return [
            self::TAG_ENTITY_PREFIX.$entityType,
            self::TAG_ENTITY_PREFIX.$entityType.':'.$entityId,
        ];
    }

    /**
     * Generate feature tag for tenant.
     */
    public function featureTag(int $organizationId): string
    {
        return self::TAG_FEATURE_PREFIX.$organizationId;
    }

    /**
     * Generate combined tags for tenant + entity.
     *
     * @param  int  $organizationId  Tenant ID
     * @param  string  $entityType  Entity type (e.g., 'event', 'guest')
     * @param  int|string|null  $entityId  Specific entity ID or null for type-wide
     * @return array<string>
     */
    public function combinedTags(int $organizationId, string $entityType, int|string|null $entityId = null): array
    {
        $tags = $this->tenantTags($organizationId);

        if ($entityId !== null) {
            $tags[] = self::TAG_ENTITY_PREFIX.$entityType.':'.$entityId;
        }

        $tags[] = self::TAG_ENTITY_PREFIX.$entityType;

        return $tags;
    }

    /**
     * Clear all application cache (use with caution).
     */
    public function flush(): bool
    {
        return $this->repository()->clear();
    }

    /**
     * Get cache repository instance.
     */
    private function repository(): Repository
    {
        return $this->cache->store((string) config('cache.tagged_store', 'redis'));
    }

    /**
     * Normalize tags to ensure consistent format.
     *
     * @param  array<string>  $tags
     * @return array<string>
     */
    private function normalizeTags(array $tags): array
    {
        return array_values(array_unique(array_filter($tags)));
    }
}
