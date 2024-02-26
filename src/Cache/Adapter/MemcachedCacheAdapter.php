<?php

namespace Phikl\Cache\Adapter;

use Phikl\Cache\Entry;

final class MemcachedCacheAdapter extends AbstractRemoteCacheAdapter
{
    private \Memcached $memcached;

    /**
     * @param MemcachedServer|array<MemcachedServer> $servers
     * @param string                                 $persistentId the persistent_id is used to create a unique connection
     *                                                             pool for the specified servers
     */
    public function __construct(MemcachedServer|array $servers, string $persistentId = 'phikl')
    {
        if (!\extension_loaded('memcached')) {
            throw new \RuntimeException('Memcached extension is not loaded');
        }

        $servers = \is_array($servers) ? $servers : [$servers];

        $this->memcached = new \Memcached($persistentId);
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
            $ttl instanceof \DateInterval ?
                (int) ((new \DateTimeImmutable())->add($ttl)->format('U') - \time())
                : ($ttl ?? 0)
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

    public function has(string $key): bool
    {
        return $this->memcached->get($key) !== false;
    }
}
