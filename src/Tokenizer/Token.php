<?php

namespace Phpkl\Tokenizer;

class Token
{
    public function __construct(
        public TokenType $type,
        public string $value,
        public string $position,
    ) {}
}
