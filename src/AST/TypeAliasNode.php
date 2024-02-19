<?php

namespace Phpkl\AST;

class TypeAliasNode extends AbstractNode
{
    public function getType(): NodeType
    {
        return NodeType::TypeAlias;
    }
}
