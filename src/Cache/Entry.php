<?php

namespace Phikl\Cache;

/**
 * @internal
 */
class Entry
{
    public function __construct(
        public string $content,
        public string $hash,
    ) {
    }
}
