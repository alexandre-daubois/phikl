<?php

namespace Phikl\Tests;

use Phikl\Cache\Entry;
use Phikl\Cache\PersistentCache;
use Phikl\Pkl;
use Phikl\PklModule;
use Phikl\Tests\Fixtures\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Pkl::class)]
class PklTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        $_ENV['PKL_CLI_BIN'] = __DIR__.'/../../vendor/bin/pkl';
        Pkl::setCache(new PersistentCache());
    }

    protected function setUp(): void
    {
        $cache = new PersistentCache();
        Pkl::setCache($cache);
    }

    protected function tearDown(): void
    {
        @unlink('.phil.cache');
    }

    public function testEvalSimpleFile(): void
    {
        $result = Pkl::eval(__DIR__.'/Fixtures/simple.pkl');

        $this->assertInstanceOf(PklModule::class, $result);
        $this->assertSame('Pkl: Configure your Systems in New Ways', $result->get('name'));
        $this->assertSame(100, $result->get('attendants'));
        $this->assertTrue($result->get('isInteractive'));
        $this->assertSame(13.37, $result->get('amountLearned'));
    }

    public function testEvalCachesOnCacheMiss(): void
    {
        $cache = new PersistentCache();
        Pkl::setCache($cache);

        $this->assertFalse($cache->has(__DIR__.'/Fixtures/simple.pkl'));
        Pkl::eval(__DIR__.'/Fixtures/simple.pkl');

        $cache->clear();
        $cache->load();

        $this->assertTrue($cache->has(__DIR__.'/Fixtures/simple.pkl'));

        unlink($cache->getCacheFile());
    }

    public function testEvalDoesntCacheOnCacheMissWithDisabledCache(): void
    {
        $cache = new PersistentCache();
        Pkl::setCache($cache);
        Pkl::toggleCache(false);

        $this->assertFalse($cache->has(__DIR__.'/Fixtures/simple.pkl'));
        Pkl::eval(__DIR__.'/Fixtures/simple.pkl');
        $this->assertFalse($cache->has(__DIR__.'/Fixtures/simple.pkl'));
    }

    public function testEvalMultipleConfigFiles(): void
    {
        $result = Pkl::eval(__DIR__.'/Fixtures/multiple.pkl');

        $this->assertInstanceOf(PklModule::class, $result);

        $this->assertInstanceOf(PklModule::class, $result->get('woodPigeon'));
        $this->assertSame('Common wood pigeon', $result->get('woodPigeon')->get('name'));
        $this->assertSame('Seeds', $result->get('woodPigeon')->get('diet'));

        $this->assertInstanceOf(PklModule::class, $result->get('woodPigeon')->get('taxonomy'));
        $this->assertSame('Columba palumbus', $result->get('woodPigeon')->get('taxonomy')->get('species'));

        $this->assertInstanceOf(PklModule::class, $result->get('stockPigeon'));
        $this->assertInstanceOf(PklModule::class, $result->get('dodo'));
    }

    public function testEvalWithCustomClass(): void
    {
        $result = Pkl::eval(__DIR__.'/Fixtures/user.pkl', User::class);

        $this->assertIsArray($result);

        $user = $result['user'];
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame(1, $user->id);
        $this->assertSame('John Doe', $user->name);
        $this->assertSame('123 Main St', $user->address->street);
        $this->assertSame('Springfield', $user->address->city);
        $this->assertSame('IL', $user->address->state);
        $this->assertSame('62701', $user->address->zip);
    }

    /**
     * Because PKL CLI tool is used only on a cache miss, we check we
     * go through the PKL CLI tool when the cache is empty. By unsetting
     * the PKL_CLI_BIN environment variable, we make sure the PKL CLI
     * tool is not found and an exception is thrown.
     *
     * @runInSeparateProcess
     */
    public function testCacheMissWithInvalidExecutable(): void
    {
        $_ENV['PKL_CLI_BIN'] = 'invalidbin';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Pkl CLI is not installed. Make sure to set the PKL_CLI_BIN environment variable or run the `phikl install` command.');

        // ensure it is a cache miss
        Pkl::setCache(new PersistentCache());
        Pkl::eval(__DIR__.'/Fixtures/simple.pkl');
    }

    /**
     * @runInSeparateProcess
     */
    public function testCacheHitWithInvalidExecutable(): void
    {
        unset($_ENV['PKL_CLI_BIN']);

        $cache = new PersistentCache();
        $cache->set('foo.pkl', new Entry(<<<JSON
{
    "name": "bar"
}
JSON, 'bar', 0));
        $cache->save();

        // pkl cli tool must not be called
        $result = Pkl::eval('foo.pkl');
        $this->assertInstanceOf(PklModule::class, $result);
        $this->assertSame('bar', $result->get('name'));

        unlink($cache->getCacheFile());
    }
}
