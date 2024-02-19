<?php

namespace Phpkl\Tests\Tokenizer;

use Phpkl\Tokenizer\Tokenizer;
use Phpkl\Tokenizer\TokenType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Tokenizer::class)]
class TokenizerTest extends TestCase
{
    public function testTokenizeIdentifierWithStringLiteral(): void
    {
        $source = <<<PKL
host = "localhost"
PKL;

        $tokens = (new Tokenizer())->tokenize($source);

        $this->assertCount(3, $tokens);

        $this->assertSame(TokenType::Identifier, $tokens[0]->type);
        $this->assertSame('host', $tokens[0]->value);

        $this->assertSame(TokenType::Symbol, $tokens[1]->type);
        $this->assertSame('=', $tokens[1]->value);

        $this->assertSame(TokenType::StringLiteral, $tokens[2]->type);
        $this->assertSame('"localhost"', $tokens[2]->value);
    }

    public function testTokenizeIdentifierWithNumber(): void
    {
        $source = <<<PKL
port = 8080
PKL;

        $tokens = (new Tokenizer())->tokenize($source);

        $this->assertCount(3, $tokens);

        $this->assertSame(TokenType::Identifier, $tokens[0]->type);
        $this->assertSame('port', $tokens[0]->value);

        $this->assertSame(TokenType::Symbol, $tokens[1]->type);
        $this->assertSame('=', $tokens[1]->value);

        $this->assertSame(TokenType::Number, $tokens[2]->type);
        $this->assertSame('8080', $tokens[2]->value);
    }

    public function testTokenizeMultipleIdentifier(): void
    {
        $source = <<<PKL
host = "localhost"
port = 8080
PKL;

        $tokens = (new Tokenizer())->tokenize($source);

        $this->assertCount(6, $tokens);

        $this->assertSame(TokenType::Identifier, $tokens[0]->type);
        $this->assertSame('host', $tokens[0]->value);

        $this->assertSame(TokenType::Symbol, $tokens[1]->type);
        $this->assertSame('=', $tokens[1]->value);

        $this->assertSame(TokenType::StringLiteral, $tokens[2]->type);
        $this->assertSame('"localhost"', $tokens[2]->value);

        $this->assertSame(TokenType::Identifier, $tokens[3]->type);
        $this->assertSame('port', $tokens[3]->value);

        $this->assertSame(TokenType::Symbol, $tokens[4]->type);
        $this->assertSame('=', $tokens[4]->value);

        $this->assertSame(TokenType::Number, $tokens[5]->type);
        $this->assertSame('8080', $tokens[5]->value);
    }

    public function testTokenizeModule(): void
    {
        $source = <<<PKL
module App.Config
PKL;

        $tokens = (new Tokenizer())->tokenize($source);

        $this->assertCount(2, $tokens);

        $this->assertSame(TokenType::Module, $tokens[0]->type);
        $this->assertSame('module', $tokens[0]->value);

        $this->assertSame(TokenType::Identifier, $tokens[1]->type);
        $this->assertSame('App.Config', $tokens[1]->value);
    }
}
