<?php

namespace Phikl\Cache\Adapter;

use Phikl\Cache\Entry;
use Psr\SimpleCache\CacheInterface;

abstract class AbstractRemoteCacheAdapter implements CacheInterface
{
    /**
     * @param iterable<non-empty-string> $keys
     *
     * @return array<non-empty-string, Entry|null>
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
     * @param iterable<string> $keys
     */
    public function deleteMultiple(iterable $keys): bool
    {
        $success = true;
        foreach ($keys as $key) {
            if (!$this->delete($key)) {
                $success = false;
            }
        }

        return $success;
    }
}
