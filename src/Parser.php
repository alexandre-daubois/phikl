<?php

namespace Phpkl;

use Phpkl\AST\AbstractNode;
use Phpkl\AST\ModuleNode;
use Phpkl\AST\PropertyNode;
use Phpkl\AST\PropertyType;
use Phpkl\AST\VariableNode;
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

    private function parseIdentifier(): PropertyNode|VariableNode
    {
        $identifierName = $this->consume(TokenType::Identifier)->value;
        $symbol = $this->consume(TokenType::Symbol);

        if ($symbol->value === '=') {
            $value = $this->consume([TokenType::StringLiteral, TokenType::Number]);

            return new VariableNode(
                $identifierName,
                $value->type === TokenType::StringLiteral ? trim($value->value, '"') : (int) $value->value
            );
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
            throw new \Exception("Unexpected token: {$token->value}, expected: " . implode(', ', $type));
        }

        $this->current++;

        return $token;
    }
}
