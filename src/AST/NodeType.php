<?php

namespace Phpkl\AST;

enum NodeType
{
    case Module;
    case Property;
    case ClassNode;
    case TypeAlias;
}
