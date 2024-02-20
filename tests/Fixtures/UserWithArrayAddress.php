<?php

namespace Phpkl\Tests\Fixtures;

class UserWithArrayAddress
{
    public int $id;
    public string $name;

    /** @var array<string, string> */
    public array $address;
}
