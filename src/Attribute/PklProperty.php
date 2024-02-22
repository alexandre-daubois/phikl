<?php

/*
 * (c) Alexandre Daubois <alex.daubois@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phikl\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class PklProperty
{
    public function __construct(public string $name)
    {
    }
}
