<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude(['vendor'])
    ->in(__DIR__)
;

$config = new PhpCsFixer\Config();

return $config->setRules([
    '@Symfony' => true,
    '@Symfony:risky' => true,
    'protected_to_private' => false,
    'nullable_type_declaration_for_default_null_value' => ['use_nullable_type_declaration' => true],
    'get_class_to_class_keyword' => true,
    'normalize_index_brace' => true,
    'trim_array_spaces' => true,
    'no_multiple_statements_per_line' => true,
    '@DoctrineAnnotation' => true,
    'yoda_style' => false,
    'array_indentation' => true,
    'blank_line_before_statement' => ['statements' => ['break', 'case', 'continue', 'declare', 'default', 'exit', 'goto', 'include', 'include_once', 'phpdoc', 'require', 'require_once', 'return', 'switch', 'throw', 'try', 'yield', 'yield_from']],
    'type_declaration_spaces' => true,
    'method_argument_space' => ['on_multiline' => 'ensure_fully_multiline'],
    'native_function_invocation' => ['include' => ['@compiler_optimized'], 'scope' => 'namespaced', 'strict' => false],
    'phpdoc_to_comment' => false,
])
    ->setFinder($finder);
