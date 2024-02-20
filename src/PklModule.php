<?php

namespace Phpkl;

use Phpkl\Attribute\PklProperty;

/**
 * @implements \ArrayAccess<string, scalar|PklModule>
 */
class PklModule implements \ArrayAccess, PklModuleInterface
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

    /**
     * @return scalar|PklModule
     */
    public function get(string $name): mixed
    {
        return $this->properties[$name];
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $toClass
     *
     * @return T
     */
    public function cast(string $toClass): object
    {
        $reflectionClass = new \ReflectionClass($toClass);
        $copy = $reflectionClass->newInstanceWithoutConstructor();

        foreach ($reflectionClass->getProperties() as $destProperty) {
            $attribute = $destProperty->getAttributes(PklProperty::class);
            $sourcePropertyName = isset($attribute[0]) ? $attribute[0]->newInstance()->name : $destProperty->name;

            if (isset($this->properties[$sourcePropertyName])) {
                $srcProperty = $this->properties[$sourcePropertyName];
                if ($srcProperty instanceof self) {
                    // it should be an object or an array in the destination class
                    $type = $destProperty->getType();
                    \assert($type instanceof \ReflectionNamedType);

                    /** @var class-string<object> $destPropertyType */
                    $destPropertyType = $type->getName();

                    if ($destPropertyType === 'array') {
                        $destProperty->setValue($copy, $srcProperty->toArray());
                    } else {
                        $destPropertyInstance = $srcProperty->cast($destPropertyType);
                        $destProperty->setValue($copy, $destPropertyInstance);
                    }
                } else {
                    $destProperty->setValue($copy, $this->properties[$sourcePropertyName]);
                }
            }
        }

        return $copy;
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

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $array = [];
        foreach ($this->properties as $key => $value) {
            if ($value instanceof self) {
                $array[$key] = $value->toArray();

                continue;
            }

            $array[$key] = $value;
        }

        return $array;
    }

    /**
     * @return array<string>
     */
    public function keys(): array
    {
        return array_keys($this->properties);
    }
}
