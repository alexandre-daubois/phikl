<?php

/*
 * (c) Alexandre Daubois <alex.daubois@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phikl\Cache;

class Entry
{
    public function __construct(
        public string $content,
        public string $hash,
        public int $timestamp,
    ) {
    }
}
