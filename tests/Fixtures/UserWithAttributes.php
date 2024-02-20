<?php

namespace Phpkl\Tests\Fixtures;

use Phpkl\Attribute\PklProperty;

class UserWithAttributes
{
    #[PklProperty('id')]
    public int $identifier;

    #[PklProperty('name')]
    public string $nameOfUser;

    #[PklProperty('address')]
    public Address $addressOfUser;
}
