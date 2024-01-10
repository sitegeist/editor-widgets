<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Cache\Backend\FileBackend;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

ExtensionManagementUtility::addTypoScript(
    'editor_widgets',
    'setup',
    "@import 'EXT:editor_widgets/Configuration/TypoScript/setup.typoscript'"
);

if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['editor_widgets_storage_size'] ?? null)) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['editor_widgets_storage_size'] = [
        'frontend' => VariableFrontend::class,
        'backend' => FileBackend::class,
        'options' => [
            'defaultLifetime' => 86400,
        ],
    ];
}

ExtensionManagementUtility::addTypoScript(
    'editor_widgets',
    'setup',
    "@import 'EXT:editor_widgets/Configuration/TypoScript/setup.typoscript'"
);

$GLOBALS['TYPO3_CONF_VARS']['BE']['stylesheets'] = 'EXT:editor_widgets/Resources/Public/Css/backend.css';
