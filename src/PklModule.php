<?php

namespace Phpkl;

class PklModule implements \ArrayAccess
{
    /**
     * @var array<string, scalar|PklModule>
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

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->properties[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->properties[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->properties[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->properties[$offset]);
    }
}
