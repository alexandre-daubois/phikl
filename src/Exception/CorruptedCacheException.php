<?php

/*
 * (c) Alexandre Daubois <alex.daubois@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phikl\Exception;

class CorruptedCacheException extends \RuntimeException
{
    public function __construct(string $cacheFile)
    {
        parent::__construct(sprintf('The cache file "%s" seems corrupted and should be generated again with the `phikl dump` command.', $cacheFile));
    }
}
