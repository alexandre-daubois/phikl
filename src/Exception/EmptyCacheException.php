<?php

namespace Phpkl\Exception;

class EmptyCacheException extends \RuntimeException
{
    public function __construct(string $cacheFile)
    {
        parent::__construct(sprintf('The cache file "%s" is empty or does not exist and should be generated again with the `phikl dump` command.', $cacheFile));
    }
}
