<?php

namespace Phikl\Cache;

use Phikl\Exception\CorruptedCacheException;
use Phikl\Exception\EmptyCacheException;

/**
 * @internal
 */
class Cache
{
    private const DEFAULT_CACHE_FILE = '.phikl.cache';

    public function __construct(private ?string $cacheFile = null)
    {
    }

    /**
     * @var array<string, Entry>|null
     */
    private ?array $entries = null;

    public function get(string $key): Entry|null
    {
        if ($this->entries === null) {
            self::load();
        }

        return $this->entries[$key] ?? null;
    }

    public function add(Entry $entry): void
    {
        $this->entries[$entry->key] = $entry;
    }

    public function clear(): void
    {
        $this->entries = null;
    }

    private function load(): void
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

    public function getCacheFile(): string
    {
        return $this->cacheFile ?? $_ENV['PHIKL_CACHE_FILE'] ?? $_SERVER['PHIKL_CACHE_FILE'] ?? self::DEFAULT_CACHE_FILE;
    }

    public function setCacheFile(string $cacheFile): void
    {
        $this->cacheFile = $cacheFile;
    }
}
