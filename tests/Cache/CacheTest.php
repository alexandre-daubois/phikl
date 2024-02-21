<?php

namespace Phpkl\Tests\Cache;

use Phpkl\Cache\Cache;
use Phpkl\Cache\Entry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Cache::class)]
class CacheTest extends TestCase
{
    public function testGet(): void
    {
        $cache = new Cache();
        $cache->add(new Entry('key', 'content'));

        $this->assertNotNull($cache->get('key'));
        $this->assertSame('content', $cache->get('key')->content);
        $this->assertSame('key', $cache->get('key')->key);
    }

    public function testAdd(): void
    {
        $cache = new Cache();
        $this->assertNull($cache->get('key'));

        $cache->add(new Entry('key', 'content'));
        $this->assertNotNull($cache->get('key'));
    }

    public function testClear(): void
    {
        $cache = new Cache();
        $cache->add(new Entry('key', 'content'));
        $this->assertNotNull($cache->get('key'));

        $cache->clear();
        $this->assertNull($cache->get('key'));
    }

    public function testSave(): void
    {
        $cache = new Cache();
        $cache->add(new Entry('key', 'content'));
        $cache->save();

        $this->assertFileExists($cache->getCacheFile());
        $cache->clear();

        // autoload the cache
        $this->assertNotNull($cache->get('key'));

        unlink($cache->getCacheFile());
    }

    public function testGetCacheFile(): void
    {
        $cache = new Cache();
        $this->assertSame('.phikl.cache', $cache->getCacheFile());
    }

    public function testSetCacheFile(): void
    {
        $cache = new Cache();
        $cache->setCacheFile('test.cache');

        $this->assertSame('test.cache', $cache->getCacheFile());
    }
}
