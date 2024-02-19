<?php

namespace Phpkl\AST;

abstract class AbstractNode
{
    abstract function getType(): NodeType;
}
