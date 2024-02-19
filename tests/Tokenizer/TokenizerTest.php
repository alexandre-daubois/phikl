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

    public function testTokenizeRemovesComments(): void
    {
        $source = <<<PKL
// Assign the host
host = "localhost"

/// then the port
/// port = 8080

/*
 * This is a multiline comment
 * and should be removed.
 *
 * module App.Config, this should not be found!
 */
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

    public function testTokenizeOtherBaseNumber(): void
    {
        $source = <<<PKL
num1 = 123
num2 = 0x012AFF
num3 = 0b00010111
num4 = 0o755
PKL;

        $tokens = (new Tokenizer())->tokenize($source);

        $this->assertCount(12, $tokens);

        $this->assertSame(TokenType::Identifier, $tokens[0]->type);
        $this->assertSame('num1', $tokens[0]->value);

        $this->assertSame(TokenType::Symbol, $tokens[1]->type);
        $this->assertSame('=', $tokens[1]->value);

        $this->assertSame(TokenType::Number, $tokens[2]->type);
        $this->assertSame('123', $tokens[2]->value);

        $this->assertSame(TokenType::Identifier, $tokens[3]->type);
        $this->assertSame('num2', $tokens[3]->value);

        $this->assertSame(TokenType::Symbol, $tokens[4]->type);
        $this->assertSame('=', $tokens[4]->value);

        $this->assertSame(TokenType::OtherBaseNumber, $tokens[5]->type);
        $this->assertSame('0x012AFF', $tokens[5]->value);

        $this->assertSame(TokenType::Identifier, $tokens[6]->type);
        $this->assertSame('num3', $tokens[6]->value);

        $this->assertSame(TokenType::Symbol, $tokens[7]->type);
        $this->assertSame('=', $tokens[7]->value);

        $this->assertSame(TokenType::OtherBaseNumber, $tokens[8]->type);
        $this->assertSame('0b00010111', $tokens[8]->value);

        $this->assertSame(TokenType::Identifier, $tokens[9]->type);
        $this->assertSame('num4', $tokens[9]->value);

        $this->assertSame(TokenType::Symbol, $tokens[10]->type);
        $this->assertSame('=', $tokens[10]->value);

        $this->assertSame(TokenType::OtherBaseNumber, $tokens[11]->type);
        $this->assertSame('0o755', $tokens[11]->value);
    }

    public function testTokenizeNumberWithSeparator(): void
    {
        $source = <<<PKL
num1 = 123_456_789
PKL;

        $tokens = (new Tokenizer())->tokenize($source);

        $this->assertCount(3, $tokens);

        $this->assertSame(TokenType::Identifier, $tokens[0]->type);
        $this->assertSame('num1', $tokens[0]->value);

        $this->assertSame(TokenType::Symbol, $tokens[1]->type);
        $this->assertSame('=', $tokens[1]->value);

        $this->assertSame(TokenType::NumberWithSeparator, $tokens[2]->type);
        $this->assertSame('123_456_789', $tokens[2]->value);
    }
}
