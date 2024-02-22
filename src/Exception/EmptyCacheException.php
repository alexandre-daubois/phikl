<?php

/*
 * (c) Alexandre Daubois <alex.daubois@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phikl\Exception;

class EmptyCacheException extends \RuntimeException
{
    public function __construct(string $cacheFile)
    {
        parent::__construct(sprintf('The cache file "%s" is empty or does not exist and should be generated again with the `phikl dump` command.', $cacheFile));
    }
}
