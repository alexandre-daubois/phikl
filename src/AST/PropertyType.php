<?php

namespace Phpkl\AST;

enum PropertyType: string
{
    case Any = 'Any'; // abstract
    case Number = 'Number'; // abstract

    case Int = 'Int'; // int64
    case Float = 'Float';
    case String = 'String';
    case Null = 'Null';
    case Boolean = 'Boolean';
    case Regex = 'Regex';
    case Duration = 'Duration';
    case DataSize = 'DataSize';

    // Aliased types
    case NonNull = 'NonNull'; // any(!is null)
    case Int8 = 'Int8';
    case Int16 = 'Int16';
    case Int32 = 'Int32';
    case Uint8 = 'Uint8';
    case Uint16 = 'Uint16';
    case Uint32 = 'Uint32';
    case Uint = 'Uint'; // uint64
    case Comparable = 'Comparable';
    case Char = 'Char'; // string(length == 1)
    case Uri = 'Uri';
    case DurationUnit = 'DurationUnit';
    case DataSizeUnit = 'DataSizeUnit';
    case Mixin = 'Mixin';

    public function isTypeAlias(): bool
    {
        return \in_array($this->value, [
            self::NonNull,
            self::Int8,
            self::Int16,
            self::Int32,
            self::Uint8,
            self::Uint16,
            self::Uint32,
            self::Uint,
            self::Comparable,
            self::Char,
            self::Uri,
            self::DurationUnit,
            self::DataSizeUnit,
            self::Mixin,
        ]);
    }
}
