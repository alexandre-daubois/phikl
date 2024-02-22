<?php

namespace Phikl\Tests\Fixtures;

use Phikl\Attribute\PklProperty;

class ClassWithAttributes
{
    #[PklProperty('firstname')]
    public string $name;

    #[PklProperty('lastname')]
    public string $surname;
}
