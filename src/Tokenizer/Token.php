<?php

namespace Phpkl\Tokenizer;

class Token
{
    public function __construct(
        public TokenType $type,
        public mixed $value,
        public string $position,
    ) {}
}
