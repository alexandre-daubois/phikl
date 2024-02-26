<?php

namespace Phikl\Cache\Adapter;

use Phikl\Cache\Entry;

/**
 * Simple implementation of the PSR-16 CacheInterface using APCu
 * for the Pkl modules evaluation cache.
 */
final class ApcuCacheAdapter extends AbstractRemoteCacheAdapter
{
    public function __construct()
    {
        if (!\extension_loaded('apcu')) {
            throw new \RuntimeException('APCu extension is not loaded');
        }

        if (!\function_exists('apcu_enabled') || !apcu_enabled()) {
            throw new \RuntimeException('APCu is not enabled');
        }
    }

    /**
     * @param non-empty-string $key
     */
    public function get(string $key, mixed $default = null): Entry|null
    {
        if ($default !== null && !$default instanceof Entry) {
            throw new \InvalidArgumentException('Default value must be null or an instance of Entry');
        }

        $entry = apcu_fetch($key);
        if ($entry === false) {
            return $default;
        }

        $entry = @unserialize($entry);
        if ($entry === false) {
            return $default;
        }

        return $entry;
    }

    public function set(string $key, mixed $value, \DateInterval|int|null $ttl = null): bool
    {
        if (!$value instanceof Entry) {
            return false;
        }

        return apcu_store(
            $key,
            serialize($value),
            $ttl instanceof \DateInterval ?
                (int) ((new \DateTimeImmutable())->add($ttl)->format('U') - \time())
                : ($ttl ?? 0)
        );
    }

    public function delete(string $key): bool
    {
        return apcu_delete($key);
    }

    /**
     * Caution, this method will clear the entire cache, not just the cache for this application.
     */
    public function clear(): bool
    {
        return apcu_clear_cache();
    }

    public function has(string $key): bool
    {
        return apcu_exists($key);
    }
}
