<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\ValueObject\PhpVersion;
use Ssch\TYPO3Rector\Configuration\Typo3Option;
use Ssch\TYPO3Rector\Set\Typo3LevelSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/Classes',
        __DIR__ . '/Configuration',
        __DIR__ . '/ext_localconf.php',
    ])
    ->withSets([
        Typo3LevelSetList::UP_TO_TYPO3_11,
    ])
    ->withPHPStanConfigs([
        Typo3Option::PHPSTAN_FOR_RECTOR_PATH,
    ])
    ->withPhpVersion(PhpVersion::PHP_81)
    ->withImportNames(importShortClasses: false, removeUnusedImports: true)
    ->withSkip([
        Rector\PostRector\Rector\NameImportingPostRector::class => [
            __DIR__ . '/ext_localconf.php',
        ],
    ]);
