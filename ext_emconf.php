<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Backend Environment Indicator',
    'description' => 'Shows the current environment (application context) and the admin role in the TYPO3 backend top bar.',
    'category' => 'be',
    'author' => 'Dirk Wenzel',
    'author_email' => '',
    'state' => 'stable',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-14.3.99',
            'backend' => '11.5.0-14.3.99',
            'php' => '8.1.0-8.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    'autoload' => [
        'psr-4' => [
            'DWenzel\\BeEnvironmentIndicator\\' => 'Classes/',
        ],
    ],
];
