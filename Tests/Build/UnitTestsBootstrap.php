<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 extension "be_environment_indicator".
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

/*
 * Locates the Composer autoloader, either of a project which requires this
 * package (vendor dir "vendor" or "app/vendor") or of the package itself
 * (".build/vendor"), and registers the test namespace.
 */
$candidates = [];
$directory = __DIR__;
while (true) {
    foreach (['vendor', 'app/vendor', '.build/vendor'] as $vendorDirectory) {
        $candidates[] = $directory . '/' . $vendorDirectory . '/autoload.php';
    }
    $parent = dirname($directory);
    if ($parent === $directory) {
        break;
    }
    $directory = $parent;
}

$classLoader = null;
foreach ($candidates as $candidate) {
    if (is_file($candidate)) {
        $classLoader = require $candidate;
        break;
    }
}

if (!$classLoader instanceof \Composer\Autoload\ClassLoader) {
    fwrite(STDERR, 'Composer autoloader not found.' . PHP_EOL);
    exit(1);
}

$classLoader->addPsr4('DWenzel\\BeEnvironmentIndicator\\Tests\\', dirname(__DIR__) . '/');
$classLoader->addPsr4('DWenzel\\BeEnvironmentIndicator\\', dirname(__DIR__, 2) . '/Classes/');

// Constants usually defined by the TYPO3 SystemEnvironmentBuilder (needed when mocking core classes)
defined('LF') || define('LF', chr(10));
defined('CR') || define('CR', chr(13));
defined('CRLF') || define('CRLF', CR . LF);
defined('TAB') || define('TAB', chr(9));
