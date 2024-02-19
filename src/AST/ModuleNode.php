<?php

namespace Phpkl\AST;

class ModuleNode extends AbstractNode
{
    public function __construct(public string $name) {}

    public function getType(): NodeType
    {
        return NodeType::Module;
    }
}
