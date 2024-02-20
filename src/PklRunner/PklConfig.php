<?php

namespace Phpkl\PklRunner;

#[\AllowDynamicProperties]
class PklConfig extends \stdClass
{
    public function __set(string $name, $value): void
    {
        $this->$name = $value;
    }

    public function __get(string $name)
    {
        return $this->$name;
    }
}
