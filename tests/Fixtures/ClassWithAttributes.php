<?php

namespace Phpkl\Tests\Fixtures;

use Phpkl\Attribute\PklProperty;

class ClassWithAttributes
{
    #[PklProperty('firstname')]
    public string $name;

    #[PklProperty('lastname')]
    public string $surname;
}
