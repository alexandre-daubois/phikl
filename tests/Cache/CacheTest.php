<?php

namespace Phpkl\Tests\Cache;

use Phpkl\Cache\Cache;
use Phpkl\Cache\Entry;
use Phpkl\Exception\CorruptedCacheException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Cache::class)]
class CacheTest extends TestCase
{
    public function testGet(): void
    {
        $cache = new Cache();
        $cache->add(new Entry('key', 'content', 'hash'));

        $this->assertNotNull($cache->get('key'));
        $this->assertSame('content', $cache->get('key')->content);
        $this->assertSame('key', $cache->get('key')->key);
    }

    public function testAdd(): void
    {
        $cache = new Cache();
        $this->assertNull($cache->get('key'));

        $cache->add(new Entry('key', 'content', 'hash'));
        $this->assertNotNull($cache->get('key'));
    }

    public function testClear(): void
    {
        $cache = new Cache();
        $cache->add(new Entry('key', 'content', 'hash'));
        $this->assertNotNull($cache->get('key'));

        $cache->clear();
        $this->assertNull($cache->get('key'));
    }

    public function testSave(): void
    {
        $cache = new Cache();
        $cache->add(new Entry('key', 'content', 'hash'));
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

    public function testGuessCacheFileWithEnv(): void
    {
        $_ENV['PHIKL_CACHE_FILE'] = 'env.cache';

        $cache = new Cache();
        $this->assertSame('env.cache', $cache->getCacheFile());

        unset($_ENV['PHIKL_CACHE_FILE']);
    }

    public function testGuessCacheFileWithServer(): void
    {
        $_SERVER['PHIKL_CACHE_FILE'] = 'server.cache';

        $cache = new Cache();
        $this->assertSame('server.cache', $cache->getCacheFile());

        unset($_SERVER['PHIKL_CACHE_FILE']);
    }

    public function testSetCacheFileIsPrioritizedOverEnv(): void
    {
        $_ENV['PHIKL_CACHE_FILE'] = 'env.cache';

        $cache = new Cache();
        $cache->setCacheFile('test.cache');

        $this->assertSame('test.cache', $cache->getCacheFile());

        unset($_ENV['PHIKL_CACHE_FILE']);
    }

    public function testGuessCacheFileFallbacksOnDefaultPath(): void
    {
        $cache = new Cache();
        $this->assertSame('.phikl.cache', $cache->getCacheFile());
    }

    public function testValidateOnValidCache(): void
    {
        $cache = new Cache();
        $cache->add(new Entry('key', 'content', \md5('content')));
        $cache->save();

        $cache->validate();

        unlink($cache->getCacheFile());

        $this->expectNotToPerformAssertions();
    }

    public function testValidateOnInvalidCache(): void
    {
        $cache = new Cache();
        $cache->add(new Entry('key', 'content', 'invalid'));
        $cache->save();

        $this->expectException(CorruptedCacheException::class);
        $this->expectExceptionMessage('The cache file ".phikl.cache" seems corrupted and should be generated again with the `phikl dump` command.');

        $cache->validate();
    }
}
