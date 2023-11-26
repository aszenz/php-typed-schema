<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
;

$config = new PhpCsFixer\Config();

return $config->setRules([
        '@Symfony' => true,
        'phpdoc_to_comment' => false,
        'no_superfluous_phpdoc_tags' => false
    ])
    ->setFinder($finder)
;
