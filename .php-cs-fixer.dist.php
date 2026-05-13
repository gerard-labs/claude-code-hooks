<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in([__DIR__ . '/src', __DIR__ . '/tests'])
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PER-CS2.0' => true,
        '@PER-CS2.0:risky' => true,
        '@PHP83Migration' => true,
        '@PHPUnit100Migration:risky' => true,
        'declare_strict_types' => true,
        'native_function_invocation' => ['include' => ['@compiler_optimized']],
        'global_namespace_import' => ['import_classes' => true, 'import_constants' => true, 'import_functions' => true],
        'final_class' => true,
        'final_internal_class' => true,
        'final_public_method_for_abstract_class' => false,
        'static_lambda' => true,
        'use_arrow_functions' => true,
        'void_return' => true,
        'no_php4_constructor' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'ordered_class_elements' => true,
        'ordered_imports' => true,
        'protected_to_private' => true,
        'self_static_accessor' => true,
        'simplified_null_return' => true,
        'single_quote' => true,
        'strict_comparison' => true,
        'strict_param' => true,
        'php_unit_strict' => true,
        'php_unit_test_class_requires_covers' => false,
    ])
    ->setFinder($finder)
    ->setCacheFile(__DIR__ . '/build/.php-cs-fixer.cache');
