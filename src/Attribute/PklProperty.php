<?php

namespace Phpkl\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class PklProperty
{
    public function __construct(public string $name)
    {
    }
}
