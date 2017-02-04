<?php

$finder = PhpCsFixer\Finder::create()
    ->in([__DIR__ . '/src', __DIR__ . '/tests']);
;

return PhpCsFixer\Config::create()
    ->setRules(array(
        '@PSR2' => true,
        'array_syntax' => ['syntax' => 'short'],
    ))
    ->setFinder($finder);
