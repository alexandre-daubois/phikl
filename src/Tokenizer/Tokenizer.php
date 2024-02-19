<?php

namespace Phpkl\Tokenizer;

use Phpkl\AST\PropertyType;

class Tokenizer
{
    private const RESERVED_KEYWORDS = [
        'module',
        'class',
        'typealias',
    ];

    /**
     * @return array<Token>
     */
    public function tokenize(string $code): array
    {
        $patterns = [
            '\b(module)\b' => TokenType::Module,
            '\b(class)\b' => TokenType::ClassToken,
            '\b(typealias)\b' => TokenType::TypeAlias,
            $this->getPropertyTypeTokenRegex() => TokenType::PropertyType,
            '(:|\[|\]|\||\{|}|,|=)' => TokenType::Symbol,
            '\b(?<!")[a-zA-Z_.][\w.]*\b(?!")' => TokenType::Identifier,
            '"[^"]*"' => TokenType::StringLiteral,
            '\b\d+\b' => TokenType::Number,
            '\b(?:0x[0-9a-fA-F]+|0b[01]+|0o[0-7]+)\b' => TokenType::OtherBaseNumber,
            '\b(?:\d{1,3}(?:_\d{3})+|\d+)\b' => TokenType::NumberWithSeparator,
            '\n' => TokenType::NewLine,
            '\s' => TokenType::Blank,
        ];

        $tokens = [];

        $code = $this->removeComments($code);
        foreach ($patterns as $pattern => $type) {
            if ($type === TokenType::Blank || $type === TokenType::NewLine) {
                continue;
            }

            \preg_match_all('/'.$pattern.'/', $code, $matches, PREG_OFFSET_CAPTURE);

            foreach ($matches[0] as $match) {
                if ($type === TokenType::Identifier && \in_array($match[0], $this->reservedKeywords(), true)) {
                    // reserved keywords are not identifiers, skip them
                    continue;
                }

                $tokens[] = new Token($type, $match[0], $match[1]);
            }
        }

        usort($tokens, static fn($a, $b) => $a->position <=> $b->position);

        return $tokens;
    }

    private function getPropertyTypeTokenRegex(): string
    {
        $values = array_map(static fn($type) => $type->value, PropertyType::cases());

        return '\b' . implode('\b|\b', $values) . '\b';
    }

    private function reservedKeywords(): array
    {
        return \array_merge(
            self::RESERVED_KEYWORDS,
            array_map(static fn($type) => $type->value, PropertyType::cases())
        );
    }

    private function removeComments(string $code): string
    {
        // single line comments
        $code = preg_replace('/\/\/.*\n/', '', $code);

        // multi line comments
        return preg_replace('/\/\*.*?\*\//s', '', $code);
    }
}
