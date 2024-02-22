<?php

namespace Phikl\Tests\Fixtures;

use Phikl\Attribute\PklProperty;

class UserWithAttributes
{
    #[PklProperty('id')]
    public int $identifier;

    #[PklProperty('name')]
    public string $nameOfUser;

    #[PklProperty('address')]
    public Address $addressOfUser;
}
