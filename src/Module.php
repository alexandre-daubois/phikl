<?php

namespace Phpkl;

class Module
{
    /**
     * @var array<string, scalar|Module>
     */
    private array $properties = [];

    public function __set(string $name, mixed $value): void
    {
        if (\is_array($value)) {
            $newValue = new self();
            foreach ($value as $key => $val) {
                $newValue->__set($key, $val);
            }

            $value = $newValue;
        }

        $this->properties[$name] = $value;
    }

    public function get(string $name): mixed
    {
        return $this->properties[$name];
    }
}
