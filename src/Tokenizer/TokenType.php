<?php

namespace Phpkl\Tokenizer;

enum TokenType
{
    case Module;
    case ModuleName;

    case ClassToken;
    case TypeAlias;
    case PropertyType;
    case Symbol;
    case Identifier;
    case StringLiteral;
    case Number;
    case OtherBaseNumber;
    case NewLine;
    case Blank;
}
