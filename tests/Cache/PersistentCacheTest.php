<?php

namespace Phikl\Tests\Cache;

use Phikl\Cache\Entry;
use Phikl\Cache\PersistentCache;
use Phikl\Exception\CorruptedCacheException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PersistentCache::class)]
class PersistentCacheTest extends TestCase
{
    protected function setUp(): void
    {
        unset($_ENV['PHIKL_CACHE_FILE'], $_SERVER['PHIKL_CACHE_FILE']);
    }

    public function testGet(): void
    {
        $cache = new PersistentCache();
        $cache->set('key', new Entry('content', 'hash', 0));

        $this->assertNotNull($cache->get('key'));
        $this->assertSame('content', $cache->get('key')->content);
    }

    public function testGetWithDefault(): void
    {
        $cache = new PersistentCache();

        $default = new Entry('default', 'hash', 0);
        $this->assertSame($default, $cache->get('key', $default));
    }

    public function testGetWithInvalidDefaultThrows(): void
    {
        $cache = new PersistentCache();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Default value must be an instance of Entry');

        $cache->get('key', 'invalid');
    }

    public function testGetIsHitButEntryIsCorrupted(): void
    {
        $cache = new PersistentCache();

        $entry = new Entry('content', \md5('content'), 0);
        $cache->set('key', $entry);

        $this->assertSame($entry, $cache->get('key'));
        $entry->hash = 'invalid';

        // not the same because entry was generated again
        $this->assertNotSame($entry, $cache->get('key'));
        $this->assertSame('content', $cache->get('key')->content);
        $this->assertSame(\md5('content'), $cache->get('key')->hash);
    }

    public function testGetIsHitButEntryIsStalled(): void
    {
        $cache = new PersistentCache();

        $entry = new Entry('content', \md5('content'), \time());
        $cache->set('foo.pkl', $entry);

        $this->assertSame($entry, $cache->get('foo.pkl'));

        // touch file to simulate a change
        touch('foo.pkl', \time() + 1);

        // not the same because entry was generated again
        $this->assertNotSame($entry, $cache->get('foo.pkl'));
        $this->assertSame('content', $cache->get('foo.pkl')->content);
        $this->assertSame(\md5('content'), $cache->get('foo.pkl')->hash);

        unlink('foo.pkl');
    }

    public function testSet(): void
    {
        $cache = new PersistentCache();
        $this->assertNull($cache->get('key'));

        $this->assertTrue($cache->set('key', new Entry('content', 'hash', 0)));
        $this->assertNotNull($cache->get('key'));
    }

    public function testSetWithInvalidObjectReturnsFalse(): void
    {
        $cache = new PersistentCache();
        $this->assertFalse($cache->set('key', 'invalid'));
    }

    public function testDelete(): void
    {
        $cache = new PersistentCache();
        $cache->set('key', new Entry('content', 'hash', 0));
        $cache->set('key2', new Entry('content', 'hash', 0));

        $this->assertNotNull($cache->get('key'));
        $this->assertNotNull($cache->get('key2'));

        $this->assertTrue($cache->delete('key'));
        $this->assertNull($cache->get('key'));
        $this->assertNotNull($cache->get('key2'));
    }

    public function testGetMultiple(): void
    {
        $cache = new PersistentCache();
        $cache->set('key', new Entry('content', 'hash', 0));
        $cache->set('key2', new Entry('content', 'hash', 0));

        $entries = $cache->getMultiple(['key', 'key2']);
        $this->assertCount(2, $entries);

        $this->assertArrayHasKey('key', $entries);
        $this->assertArrayHasKey('key2', $entries);

        $this->assertNotNull($entries['key']);
        $this->assertNotNull($entries['key2']);
    }

    public function testSetMultiple(): void
    {
        $cache = new PersistentCache();
        $this->assertNull($cache->get('key'));
        $this->assertNull($cache->get('key2'));

        $entries = [
            'key' => new Entry('content', 'hash', 0),
            'key2' => new Entry('content', 'hash', 0),
        ];

        $this->assertTrue($cache->setMultiple($entries));
        $this->assertNotNull($cache->get('key'));
        $this->assertNotNull($cache->get('key2'));
    }

    public function testDeleteMultiple(): void
    {
        $cache = new PersistentCache();
        $cache->set('key', new Entry('content', 'hash', 0));
        $cache->set('key2', new Entry('content', 'hash', 0));

        $this->assertNotNull($cache->get('key'));
        $this->assertNotNull($cache->get('key2'));

        $this->assertTrue($cache->deleteMultiple(['key', 'key2']));
        $this->assertNull($cache->get('key'));
        $this->assertNull($cache->get('key2'));
    }

    public function testHas(): void
    {
        $cache = new PersistentCache();
        $cache->set('key', new Entry('content', 'hash', 0));

        $this->assertTrue($cache->has('key'));
        $this->assertFalse($cache->has('invalid'));
    }

    public function testClear(): void
    {
        $cache = new PersistentCache();
        $cache->set('key', new Entry('content', 'hash', 0));
        $this->assertNotNull($cache->get('key'));

        $cache->clear();
        $this->assertNull($cache->get('key'));
    }

    public function testSave(): void
    {
        $cache = new PersistentCache();
        $cache->set('key', new Entry('content', 'hash', 0));
        $cache->save();

        $this->assertFileExists($cache->getCacheFile());
        $cache->clear();

        $cache->load();
        $this->assertNotNull($cache->get('key'));

        unlink($cache->getCacheFile());
    }

    public function testGetCacheFile(): void
    {
        $cache = new PersistentCache();
        $this->assertSame('.phikl.cache', $cache->getCacheFile());
    }

    public function testSetCacheFile(): void
    {
        $cache = new PersistentCache();
        $cache->setCacheFile('test.cache');

        $this->assertSame('test.cache', $cache->getCacheFile());
    }

    public function testGuessCacheFileWithEnv(): void
    {
        $_ENV['PHIKL_CACHE_FILE'] = 'env.cache';

        $cache = new PersistentCache();
        $this->assertSame('env.cache', $cache->getCacheFile());

        unset($_ENV['PHIKL_CACHE_FILE']);
    }

    public function testGuessCacheFileWithServer(): void
    {
        $_SERVER['PHIKL_CACHE_FILE'] = 'server.cache';

        $cache = new PersistentCache();
        $this->assertSame('server.cache', $cache->getCacheFile());

        unset($_SERVER['PHIKL_CACHE_FILE']);
    }

    public function testSetCacheFileIsPrioritizedOverEnv(): void
    {
        $_ENV['PHIKL_CACHE_FILE'] = 'env.cache';

        $cache = new PersistentCache();
        $cache->setCacheFile('test.cache');

        $this->assertSame('test.cache', $cache->getCacheFile());

        unset($_ENV['PHIKL_CACHE_FILE']);
    }

    public function testGuessCacheFileFallbacksOnDefaultPath(): void
    {
        $cache = new PersistentCache();
        $this->assertSame('.phikl.cache', $cache->getCacheFile());
    }

    public function testValidateOnValidCache(): void
    {
        $cache = new PersistentCache();
        $cache->set('key', new Entry('content', \md5('content'), 0));
        $cache->save();

        $cache->validate();

        unlink($cache->getCacheFile());

        $this->expectNotToPerformAssertions();
    }

    public function testValidateOnInvalidCache(): void
    {
        $cache = new PersistentCache();
        $cache->set('key', new Entry('content', 'invalid', 0));
        $cache->save();

        $this->expectException(CorruptedCacheException::class);
        $this->expectExceptionMessage('The cache file ".phikl.cache" seems corrupted and should be generated again with the `phikl dump` command.');

        $cache->validate();
    }
}
