<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\TaggedCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class TaggedCacheTest extends TestCase
{
    use RefreshDatabase;

    private TaggedCache $taggedCache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->taggedCache = app(TaggedCache::class);
        Cache::flush();
    }

    public function test_remember_stores_and_retrieves_data_with_tags(): void
    {
        $result = $this->taggedCache->remember(
            'test-key',
            ['tenant:123'],
            fn () => 'test-value',
            60,
        );

        $this->assertSame('test-value', $result);
        $this->assertTrue($this->taggedCache->has('test-key'));
    }

    public function test_put_stores_data_with_tags(): void
    {
        $this->taggedCache->put(
            'test-key',
            ['tenant:123', 'entity:event'],
            'test-value',
            60,
        );

        $this->assertSame('test-value', $this->taggedCache->get('test-key'));
    }

    public function test_forget_removes_cached_item(): void
    {
        $this->taggedCache->put('test-key', ['tenant:123'], 'value', 60);
        $this->assertTrue($this->taggedCache->has('test-key'));

        $this->taggedCache->forget('test-key');
        $this->assertFalse($this->taggedCache->has('test-key'));
    }

    public function test_flush_tags_invalidates_all_cache_with_tag(): void
    {
        $this->taggedCache->put('key1', ['tenant:123', 'type:A'], 'value1', 60);
        $this->taggedCache->put('key2', ['tenant:123', 'type:B'], 'value2', 60);
        $this->taggedCache->put('key3', ['tenant:456', 'type:A'], 'value3', 60);

        $this->taggedCache->flushTags(['tenant:123']);

        $this->assertFalse($this->taggedCache->has('key1'));
        $this->assertFalse($this->taggedCache->has('key2'));
        // key3 should still exist since it has different tenant tag
        $this->assertTrue($this->taggedCache->has('key3'));
    }

    public function test_flush_tenant_invalidates_all_tenant_cache(): void
    {
        $this->taggedCache->put('key1', ['tenant:123'], 'value1', 60);
        $this->taggedCache->put('key2', ['tenant:123'], 'value2', 60);
        $this->taggedCache->put('key3', ['tenant:456'], 'value3', 60);

        $this->taggedCache->flushTenant(123);

        $this->assertFalse($this->taggedCache->has('key1'));
        $this->assertFalse($this->taggedCache->has('key2'));
        $this->assertTrue($this->taggedCache->has('key3')); // Different tenant
    }

    public function test_flush_entity_invalidates_entity_cache(): void
    {
        $this->taggedCache->put('key1', ['entity:event:1'], 'value1', 60);
        $this->taggedCache->put('key2', ['entity:event:2'], 'value2', 60);
        $this->taggedCache->put('key3', ['entity:guest'], 'value3', 60);

        $this->taggedCache->flushEntity('event', 1);

        $this->assertFalse($this->taggedCache->has('key1'));
        $this->assertTrue($this->taggedCache->has('key2')); // Different event
        $this->assertTrue($this->taggedCache->has('key3')); // Different entity type
    }

    public function test_flush_features_invalidates_feature_cache(): void
    {
        $this->taggedCache->put('key1', ['feature:123'], 'value1', 60);
        $this->taggedCache->put('key2', ['tenant:456'], 'value2', 60);

        $this->taggedCache->flushFeatures(123);

        $this->assertFalse($this->taggedCache->has('key1'));
        $this->assertTrue($this->taggedCache->has('key2')); // Different tenant
    }

    public function test_tenant_tags_generates_correct_format(): void
    {
        $tags = $this->taggedCache->tenantTags(123);

        $this->assertSame(['tenant:123'], $tags);
    }

    public function test_entity_tags_generates_correct_format(): void
    {
        $tags = $this->taggedCache->entityTags('event', 456);

        $this->assertSame(['entity:event', 'entity:event:456'], $tags);
    }

    public function test_feature_tag_generates_correct_format(): void
    {
        $tag = $this->taggedCache->featureTag(789);

        $this->assertSame('feature:789', $tag);
    }

    public function test_combined_tags_generates_correct_format(): void
    {
        // With entity ID
        $tags = $this->taggedCache->combinedTags(123, 'event', 456);
        $this->assertSame(['tenant:123', 'entity:event:456', 'entity:event'], $tags);

        // Without entity ID
        $tags = $this->taggedCache->combinedTags(123, 'event', null);
        $this->assertSame(['tenant:123', 'entity:event'], $tags);
    }

    public function test_remember_uses_default_ttl_when_not_specified(): void
    {
        $result = $this->taggedCache->remember(
            'test-key',
            ['tenant:123'],
            fn () => 'test-value',
        );

        $this->assertSame('test-value', $result);
        $this->assertTrue($this->taggedCache->has('test-key'));
    }

    public function test_flush_clears_all_cache(): void
    {
        $this->taggedCache->put('key1', ['tenant:123'], 'value1', 60);
        $this->taggedCache->put('key2', ['tenant:456'], 'value2', 60);

        $this->taggedCache->flush();

        $this->assertFalse($this->taggedCache->has('key1'));
        $this->assertFalse($this->taggedCache->has('key2'));
    }

    public function test_multiple_tags_can_be_flushed(): void
    {
        $this->taggedCache->put('key1', ['tenant:123', 'type:A', 'subtype:X'], 'value1', 60);
        $this->taggedCache->put('key2', ['tenant:456', 'type:A', 'subtype:Y'], 'value2', 60);
        $this->taggedCache->put('key3', ['tenant:123', 'type:B'], 'value3', 60);

        // Flushing type:A should invalidate key1 and key2 (both have type:A tag)
        // but key3 should remain since it has type:B
        $this->taggedCache->flushTags(['type:A']);

        $this->assertFalse($this->taggedCache->has('key1'));
        $this->assertFalse($this->taggedCache->has('key2'));
        $this->assertTrue($this->taggedCache->has('key3'));
    }

    public function test_complex_tag_invalidation_scenario(): void
    {
        // Simulating a real-world scenario:
        // Organization 123 has events and guests
        $this->taggedCache->put('events:list', ['tenant:123', 'entity:event'], 'events-data', 60);
        $this->taggedCache->put('guests:list', ['tenant:123', 'entity:guest'], 'guests-data', 60);
        $this->taggedCache->put('event:1', ['tenant:123', 'entity:event:1'], 'event-1-data', 60);
        $this->taggedCache->put('guest:1', ['tenant:123', 'entity:guest:1'], 'guest-1-data', 60);

        // Organization 456 has its own data
        $this->taggedCache->put('events:list', ['tenant:456', 'entity:event'], 'events-456-data', 60);

        // When event 1 is updated, flush its specific cache
        $this->taggedCache->flushEntity('event', 1);

        $this->assertFalse($this->taggedCache->has('event:1'));
        $this->assertTrue($this->taggedCache->has('events:list')); // Still cached (tenant:123)
        $this->assertTrue($this->taggedCache->has('guests:list')); // Not affected
        $this->assertTrue($this->taggedCache->has('guest:1')); // Not affected
        $this->assertTrue($this->taggedCache->has('events:list')); // Org 456 still cached

        // Now flush entire tenant 123 cache
        $this->taggedCache->flushTenant(123);

        $this->assertFalse($this->taggedCache->has('events:list')); // Org 123's events list
        $this->assertFalse($this->taggedCache->has('guests:list')); // Org 123's guests list
        $this->assertFalse($this->taggedCache->has('guest:1')); // Org 123's guest

        // Org 456's cache should remain
        $org456Data = $this->taggedCache->get('events:list');
        $this->assertNotNull($org456Data);
    }
}
