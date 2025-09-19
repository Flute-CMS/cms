<?php

use PhpCsFixer\Finder;
use PhpCsFixer\Config;
use PhpCsFixer\Runner\Parallel\ParallelConfig;

$finder = Finder::create()
    ->in([__DIR__ . '/app/Core', __DIR__ . '/app/Modules'])
    ->exclude(['vendor', 'Tests', 'tests', 'storage', 'bootstrap/cache'])
    ->notPath('Database/Entities')
    ->name('*.php')
    ->notName('*.blade.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

$config = new Config();

return $config
    ->setCacheFile(__DIR__ . '/.php-cs-fixer.cache')
    ->setRiskyAllowed(true)
    ->setParallelConfig(new ParallelConfig())
    ->setIndent('    ')
    ->setLineEnding("\n")
    ->setRules([
        // Base
        '@PSR12' => true,
        '@PHP81Migration' => true,

        // Imports
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'blank_line_between_import_groups' => true,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_functions' => true,
            'import_constants' => true,
        ],
        'single_import_per_statement' => true,
        'no_unused_imports' => true,
        'no_leading_import_slash' => true,

        // Class structure + indents
        'class_attributes_separation' => [
            'elements' => [
                'const'    => 'one',
                'property' => 'one',
                'method'   => 'one',
                'trait_import' => 'none',
            ],
        ],
        'ordered_class_elements' => [
            'order' => [
                'use_trait',
                'constant_public', 'constant_protected', 'constant_private',
                'property_public', 'property_protected', 'property_private',
                'construct',
                'method_public', 'method_protected', 'method_private',
            ],
            'sort_algorithm' => 'none',
        ],
        'blank_line_before_statement' => [
            'statements' => ['break','continue','declare','return','throw','try'],
        ],
        'array_indentation' => true,
        'trailing_comma_in_multiline' => true,
        'method_chaining_indentation' => true,

        // Spaces/operators
        'unary_operator_spaces' => true,
        'binary_operator_spaces' => true,
        'not_operator_with_successor_space' => false,
        'no_multiline_whitespace_around_double_arrow' => true,
        'logical_operators' => true,

        // Modern syntax / simplifications
        'new_with_parentheses' => true,
        'get_class_to_class_keyword' => true,
        'use_arrow_functions' => true,
        'static_lambda' => true,
        'simplified_if_return' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'nullable_type_declaration_for_default_null_value' => true,
        'constant_case' => ['case' => 'lower'], // true/false/null

        // DocBlocks
        'phpdoc_scalar' => true,
        'phpdoc_single_line_var_spacing' => true,
        'phpdoc_var_without_name' => true,
        'phpdoc_order' => true,
        'phpdoc_types_order' => [
            'null_adjustment' => 'always_last',
            'sort_algorithm' => 'none',
        ],
        'phpdoc_trim' => true,
        'phpdoc_trim_consecutive_blank_line_separation' => true,
        'no_superfluous_phpdoc_tags' => [
            'allow_mixed' => true,
            'remove_inheritdoc' => false,
        ],
        'phpdoc_to_comment' => true,

        // Strings and heredoc/nowdoc
        'explicit_string_variable' => true,
        'heredoc_indentation' => true,

        // Formatting arguments
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
            'keep_multiple_spaces_after_comma' => true,
        ],
        'array_syntax' => ['syntax' => 'short'],
        'single_trait_insert_per_statement' => true,
    ])
    ->setFinder($finder);
