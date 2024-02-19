<?php

namespace Phpkl\Tests;

use Phpkl\AST\ModuleNode;
use Phpkl\AST\PropertyNode;
use Phpkl\AST\PropertyType;
use Phpkl\AST\VariableNode;
use Phpkl\Parser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Parser::class)]
class ParserTest extends TestCase
{
    public function testParseModuleName()
    {
        $source = <<<PKL
module Application.MyModule.com
PKL;

        $parser = new Parser();
        $node = $parser->parse($source)[0];

        $this->assertInstanceOf(ModuleNode::class, $node);
        $this->assertSame('Application.MyModule.com', $node->name);
    }

    public function testParseIdentifierWithStringLiteral()
    {
        $source = <<<PKL
host = "localhost"
PKL;

        $parser = new Parser();
        $node = $parser->parse($source)[0];

        $this->assertInstanceOf(VariableNode::class, $node);
        $this->assertSame('host', $node->getName());
        $this->assertSame('localhost', $node->getValue());
    }

    public function testParseIdentifierWithNumber()
    {
        $source = <<<PKL
port = 8080
PKL;

        $parser = new Parser();
        $node = $parser->parse($source)[0];

        $this->assertInstanceOf(VariableNode::class, $node);
        $this->assertSame('port', $node->getName());
        $this->assertSame(8080, $node->getValue());
    }

    public function testParseMultipleIdentifier()
    {
        $source = <<<PKL
host = "localhost"
port = 8080
PKL;

        $parser = new Parser();
        $nodes = $parser->parse($source);

        $this->assertCount(2, $nodes);

        $this->assertInstanceOf(VariableNode::class, $nodes[0]);
        $this->assertSame('host', $nodes[0]->getName());
        $this->assertSame('localhost', $nodes[0]->getValue());

        $this->assertInstanceOf(VariableNode::class, $nodes[1]);
        $this->assertSame('port', $nodes[1]->getName());
        $this->assertSame(8080, $nodes[1]->getValue());
    }

    public function testParseProperty(): void
    {
        $source = <<<PKL
host: String
port: Uint16
PKL;

        $parser = new Parser();
        $nodes = $parser->parse($source);

        $node = $nodes[0];
        $this->assertInstanceOf(PropertyNode::class, $node);
        $this->assertSame('host', $node->getName());
        $this->assertSame(PropertyType::String, $node->getPropertyType());

        $node = $nodes[1];
        $this->assertInstanceOf(PropertyNode::class, $node);
        $this->assertSame('port', $node->getName());
        $this->assertSame(PropertyType::Uint16, $node->getPropertyType());
    }
}
