<?php

namespace Phpkl\AST;

class PropertyNode extends AbstractNode
{
    public function __construct(
        private string $name,
        private PropertyType $propertyType,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getPropertyType(): PropertyType
    {
        return $this->propertyType;
    }

    public function getType(): NodeType
    {
        return NodeType::Property;
    }
}
