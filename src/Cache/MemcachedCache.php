<?php

namespace Phikl\Cache;

use Psr\SimpleCache\CacheInterface;

final class MemcachedCache implements CacheInterface
{
    private \Memcached $memcached;

    /**
     * @param MemcachedServer|array<MemcachedServer> $servers
     */
    public function __construct(MemcachedServer|array $servers)
    {
        if (!\extension_loaded('memcached')) {
            throw new \RuntimeException('Memcached extension is not loaded');
        }

        $servers = \is_array($servers) ? $servers : [$servers];

        $this->memcached = new \Memcached('phikl');
        foreach ($servers as $server) {
            $this->memcached->addServer($server->host, $server->port);
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

        $entry = $this->memcached->get($key);
        if ($this->memcached->getResultCode() === \Memcached::RES_NOTFOUND) {
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

        return $this->memcached->set(
            $key,
            serialize($value),
            $ttl instanceof \DateInterval ? (int) ($ttl->format('U')) - \time() : ($ttl ?? 0)
        );
    }

    public function delete(string $key): bool
    {
        return $this->memcached->delete($key);
    }

    /**
     * Caution, this method will clear the entire cache, not just the cache for this application.
     */
    public function clear(): bool
    {
        return $this->memcached->flush();
    }

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

    public function has(string $key): bool
    {
        return $this->memcached->get($key) !== false;
    }
}
