<?php

namespace Phikl\Cache;

final readonly class MemcachedServer
{
    public function __construct(
        public string $host,
        public int $port,
    ) {
    }
}
