<?php

// ES modules, TYPO3 12 and later. TYPO3 11 uses the RequireJS module
// Resources/Public/JavaScript/EnvironmentIndicator.js instead.
return [
    'dependencies' => ['backend'],
    'imports' => [
        '@dwenzel/be-environment-indicator/' => 'EXT:be_environment_indicator/Resources/Public/JavaScript/',
    ],
];
