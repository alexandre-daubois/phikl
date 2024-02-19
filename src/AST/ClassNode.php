<?php

namespace Phpkl\AST;

class ClassNode extends AbstractNode
{
    public function getType(): NodeType
    {
        return NodeType::ClassNode;
    }
}
