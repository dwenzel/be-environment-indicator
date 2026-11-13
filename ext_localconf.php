<?php

defined('TYPO3') or die();

// TYPO3 12 and later register toolbar items by autoconfiguration (see Configuration/Services.yaml).
if ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() < 12) {
    $GLOBALS['TYPO3_CONF_VARS']['BE']['toolbarItems'][1763030400]
        = \DWenzel\BeEnvironmentIndicator\Backend\ToolbarItems\EnvironmentToolbarItem::class;
}
