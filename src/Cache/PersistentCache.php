<?php

/*
 * (c) Alexandre Daubois <alex.daubois@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phikl\Cache;

use Phikl\Exception\CorruptedCacheException;
use Phikl\Exception\EmptyCacheException;
use Psr\SimpleCache\CacheInterface;

final class PersistentCache implements CacheInterface
{
    private const DEFAULT_CACHE_FILE = '.phikl.cache';

    public function __construct(private ?string $cacheFile = null)
    {
    }

    /**
     * @var array<string, Entry>|null
     */
    private ?array $entries = null;

    /**
     * This gets an entry from the cache. If the entry is not found, it returns the default value.
     * If the default value is not an instance of Entry, it throws an exception.
     * If the entry is found, but it is corrupted or stalled, it is refreshed.
     *
     * @throws \InvalidArgumentException
     */
    public function get(string $key, mixed $default = null): Entry|null
    {
        $entry = $this->entries[$key] ?? null;
        $isHit = $entry !== null;

        if ($isHit) {
            $actualHash = \md5($entry->content);
            if ($entry->hash !== $actualHash || $entry->timestamp < \filemtime($key)) {
                // cache is either corrupted or outdated, refresh it
                unset($this->entries[$key]);

                $entry = new Entry($entry->content, $actualHash, \time());
                $this->set($key, $entry);

                $this->save();
            }
        }

        if ($default !== null && !$default instanceof Entry) {
            throw new \InvalidArgumentException('Default value must be an instance of Entry');
        }

        return $entry ?? $default;
    }

    /**
     * This sets an entry in the cache. If the value is not an instance of Entry, it returns false.
     */
    public function set(string $key, mixed $value, \DateInterval|int|null $ttl = null): bool
    {
        if (!$value instanceof Entry) {
            return false;
        }

        $this->entries[$key] = $value;

        return true;
    }

    /**
     * Deletes an entry from the cache.
     */
    public function delete(string $key): true
    {
        unset($this->entries[$key]);

        return true;
    }

    /**
     * This gets multiple entries from the cache. If an entry is not found, it returns the default value.
     * The default value must be an instance of Entry.
     *
     * @return array<string, Entry|null>
     */
    public function getMultiple(iterable $keys, mixed $default = null): array
    {
        $entries = [];
        foreach ($keys as $key) {
            $entries[$key] = $this->get($key, $default);
        }

        return $entries;
    }

    /**
     * Sets multiple entries in the cache. If a value is not an instance of Entry, it returns false. Ttl is ignored.
     *
     * @param iterable<string, Entry> $values
     */
    public function setMultiple(iterable $values, \DateInterval|int|null $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            if (!$this->set($key, $value, $ttl)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Deletes multiple entries from the cache.
     */
    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    /**
     * Whether an entry exists in the cache.
     */
    public function has(string $key): bool
    {
        return \array_key_exists($key, $this->entries ?? []);
    }

    /**
     * Clears the entire cache and always returns true.
     */
    public function clear(): true
    {
        $this->entries = null;

        return true;
    }

    /**
     * Loads the cache from the file, guessed from the defined file, the environment variables if any, or
     * from the default file.
     */
    public function load(): void
    {
        $cacheFile = $this->getCacheFile();
        if (!file_exists($cacheFile)) {
            return;
        }

        $content = file_get_contents($cacheFile);
        if ($content === false) {
            return;
        }

        $this->entries = unserialize($content, ['allowed_classes' => [self::class, Entry::class]]) ?: [];
    }

    /**
     * Saves the cache to the file, guessed from the defined file, the environment variables if any, or
     * from the default file.
     */
    public function save(): void
    {
        file_put_contents($this->getCacheFile(), serialize($this->entries));
    }

    /**
     * @throws EmptyCacheException
     * @throws CorruptedCacheException
     */
    public function validate(): void
    {
        if ($this->entries === null) {
            $this->load();
        }

        if ($this->entries === null) {
            throw new EmptyCacheException($this->getCacheFile());
        }

        foreach ($this->entries as $entry) {
            if ($entry->hash !== \md5($entry->content)) {
                throw new CorruptedCacheException($this->getCacheFile());
            }
        }
    }

    /**
     * Guesses the cache file by falling back to the environment variables or the default file.
     */
    public function getCacheFile(): string
    {
        return $this->cacheFile ?? $_ENV['PHIKL_CACHE_FILE'] ?? $_SERVER['PHIKL_CACHE_FILE'] ?? self::DEFAULT_CACHE_FILE;
    }

    /**
     * Sets an explicit cache file, which takes precedence over the environment variables.
     */
    public function setCacheFile(string $cacheFile): void
    {
        $this->cacheFile = $cacheFile;
    }
}
