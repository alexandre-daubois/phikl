<?php

namespace Phpkl\Cache;

/**
 * @internal
 */
class Entry
{
    /**
     * @param string $key Likely a file path
     */
    public function __construct(
        public string $key,
        public string $content,
    ) {
    }
}
