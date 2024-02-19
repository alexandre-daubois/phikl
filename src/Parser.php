<?php

namespace Phpkl;

use Phpkl\AST\AbstractNode;
use Phpkl\AST\ModuleNode;
use Phpkl\AST\PropertyNode;
use Phpkl\AST\PropertyType;
use Phpkl\AST\AssignmentNode;
use Phpkl\Tokenizer\Token;
use Phpkl\Tokenizer\Tokenizer;
use Phpkl\Tokenizer\TokenType;

class Parser
{
    private int $current = 0;
    private array $tokens = [];

    /**
     * @return array<AbstractNode>
     */
    public function parse(string $source): array
    {
        $this->tokens = (new Tokenizer())->tokenize($source);

        $parsed = [];
        while ($this->current < count($this->tokens)) {
            $token = $this->tokens[$this->current];

            $parsed[] = match ($token->type) {
                TokenType::Module => $this->parseModule(),
                TokenType::Identifier => $this->parseIdentifier(),
                default => throw new \Exception("Unexpected token: {$token->value}"),
            };
        }

        return $parsed;
    }

    private function parseModule(): ModuleNode
    {
        $this->consume(TokenType::Module);
        $moduleName = $this->consume(TokenType::Identifier);

        return new ModuleNode($moduleName->value);
    }

    private function parseIdentifier(): PropertyNode|AssignmentNode
    {
        $identifierName = $this->consume(TokenType::Identifier)->value;
        $symbol = $this->consume(TokenType::Symbol);

        if ($symbol->value === '=') {
            return $this->parseAssignment($identifierName);
        } elseif ($symbol->value === ':') {
            $value = $this->consume(TokenType::PropertyType);

            return new PropertyNode($identifierName, PropertyType::from($value->value));
        }

        throw new \Exception("Unexpected token: {$symbol->value}");
    }

    private function consume(TokenType|array $type): Token
    {
        $type = \is_array($type) ? $type : [$type];
        $token = $this->tokens[$this->current];

        if (!\in_array($token->type, $type, true)) {
            throw new \Exception("Unexpected token: {$token->value}");
        }

        $this->current++;

        return $token;
    }

    private function parseAssignment(string $identifierName): AssignmentNode
    {
        $value = $this->consume([TokenType::StringLiteral, TokenType::Number, TokenType::OtherBaseNumber]);

        if ($value->type === TokenType::OtherBaseNumber) {
            $value->value = match (substr($value->value, 0, 2)) {
                '0x' => hexdec(substr($value->value, 2)),
                '0b' => bindec(substr($value->value, 2)),
                '0o' => octdec(substr($value->value, 2)),
                default => throw new \Exception("Unexpected token: {$value->value}"),
            };
        }

        return new AssignmentNode(
            $identifierName,
            $value->type === TokenType::StringLiteral ? trim($value->value, '"') : (int) $value->value
        );
    }
}
