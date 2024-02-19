<?php

namespace Phpkl\AST;

class VariableNode extends AbstractNode
{
    public function __construct(
        private string $name,
        private int|string $value,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): int|string
    {
        return $this->value;
    }

    public function getType(): NodeType
    {
        return NodeType::Property;
    }
}
