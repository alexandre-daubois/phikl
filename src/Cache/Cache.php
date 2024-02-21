<?php

namespace Phpkl\Cache;

use Phpkl\Exception\CorruptedCacheException;

/**
 * @internal
 */
class Cache
{
    private string $cacheFile = '.phikl.cache';

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
        if (!file_exists($this->cacheFile)) {
            return;
        }

        $content = file_get_contents($this->cacheFile);
        if ($content === false) {
            return;
        }

        $this->entries = unserialize($content, ['allowed_classes' => [self::class, Entry::class]]) ?: [];

        $this->validate();
    }

    public function save(): void
    {
        file_put_contents($this->cacheFile, serialize($this->entries));
    }

    public function validate(): void
    {
        if ($this->entries === null) {
            $this->load();

            return;
        }

        foreach ($this->entries as $entry) {
            if ($entry->hash !== \md5($entry->content)) {
                throw new CorruptedCacheException($this->cacheFile);
            }
        }
    }

    public function getCacheFile(): string
    {
        return $this->cacheFile;
    }

    public function setCacheFile(string $cacheFile): void
    {
        $this->cacheFile = $cacheFile;
    }
}
