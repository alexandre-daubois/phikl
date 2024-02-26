<?php

namespace Phikl\Tests\Cache\Adapter;

use Phikl\Cache\Adapter\MemcachedCacheAdapter;
use Phikl\Cache\Adapter\MemcachedServer;
use Phikl\Cache\Entry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

#[RequiresPhpExtension('memcached')]
#[CoversClass(MemcachedCacheAdapter::class)]
class MemcachedCacheTest extends TestCase
{
    protected function setUp(): void
    {
        if (false === \fsockopen('localhost', 11211)) {
            $this->markTestSkipped('Memcached is not running');
        }
    }

    private function createMemcachedCache(): MemcachedCacheAdapter
    {
        return new MemcachedCacheAdapter(new MemcachedServer('localhost', 11211));
    }

    public function testGetWithDefaultOtherThanEntryInstance(): void
    {
        $cache = $this->createMemcachedCache();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Default value must be null or an instance of Entry');

        $cache->get('key', 'invalid');
    }

    public function testGetReturnsDefaultIfKeyDoesNotExist(): void
    {
        $cache = $this->createMemcachedCache();

        $entry = new Entry('content', 'hash', 0);

        $this->assertNull($cache->get('nonexistent'));
        $this->assertSame($entry, $cache->get('nonexistent', $entry));
        $this->assertFalse($cache->has('nonexistent'));
    }

    public function testGetOnValidSetEntry(): void
    {
        $cache = $this->createMemcachedCache();

        $entry = new Entry('content', 'hash', $time = \time());

        $this->assertTrue($cache->set('key', $entry));

        $entry = $cache->get('key');
        $this->assertInstanceOf(Entry::class, $entry);
        $this->assertSame('content', $entry->content);
        $this->assertSame('hash', $entry->hash);
        $this->assertSame($time, $entry->timestamp);
    }

    public function testSetReturnsFalseOnInvalidEntry(): void
    {
        $cache = $this->createMemcachedCache();

        $this->assertFalse($cache->set('key', 'invalid'));
    }

    public function testDeleteEntry(): void
    {
        $cache = $this->createMemcachedCache();

        $entry = new Entry('content', 'hash', 0);
        $cache->set('key', $entry);

        $this->assertTrue($cache->delete('key'));
        $this->assertNull($cache->get('key'));
    }

    public function testClear(): void
    {
        $cache = $this->createMemcachedCache();

        $entry = new Entry('content', 'hash', 0);
        $cache->set('key', $entry);

        $this->assertTrue($cache->clear());
        $this->assertNull($cache->get('key'));
    }

    public function testGetSetMultiple(): void
    {
        $cache = $this->createMemcachedCache();

        $entry1 = new Entry('content1', 'hash1', 0);
        $entry2 = new Entry('content2', 'hash2', 0);
        $entry3 = new Entry('content3', 'hash3', 0);

        $cache->setMultiple([
            'key1' => $entry1,
            'key2' => $entry2,
            'key3' => $entry3,
        ]);

        $entries = $cache->getMultiple(['key1', 'key2', 'key3']);

        $this->assertArrayHasKey('key1', $entries);
        $this->assertArrayHasKey('key2', $entries);
        $this->assertArrayHasKey('key3', $entries);

        $this->assertInstanceOf(Entry::class, $entries['key1']);
        $this->assertSame('content1', $entries['key1']->content);
        $this->assertSame('hash1', $entries['key1']->hash);

        $this->assertInstanceOf(Entry::class, $entries['key2']);
        $this->assertSame('content2', $entries['key2']->content);
        $this->assertSame('hash2', $entries['key2']->hash);

        $this->assertInstanceOf(Entry::class, $entries['key3']);
        $this->assertSame('content3', $entries['key3']->content);
        $this->assertSame('hash3', $entries['key3']->hash);
    }

    public function testDeleteMultiple(): void
    {
        $cache = $this->createMemcachedCache();

        $entry1 = new Entry('content1', 'hash1', 0);
        $entry2 = new Entry('content2', 'hash2', 0);
        $entry3 = new Entry('content3', 'hash3', 0);

        $cache->setMultiple([
            'key1' => $entry1,
            'key2' => $entry2,
            'key3' => $entry3,
        ]);

        $this->assertTrue($cache->deleteMultiple(['key1', 'key2']));
        $this->assertNull($cache->get('key1'));
        $this->assertNull($cache->get('key2'));
        $this->assertNotNull($cache->get('key3'));
    }

    public function testHas(): void
    {
        $cache = $this->createMemcachedCache();

        $entry = new Entry('content', 'hash', 0);
        $cache->set('key', $entry);

        $this->assertTrue($cache->has('key'));
        $this->assertFalse($cache->has('invalid'));
    }
}
