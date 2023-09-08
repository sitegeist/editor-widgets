<?php

declare(strict_types=1);

defined('TYPO3') || die();

$tempColumns = [
    'tx_widget_mirror_max_size' => [
        'label' => 'LLL:EXT:widget_mirror/Resources/Private/Language/backend.xlf:sys_file_storage.widget_mirror.max_storage_size',
        'description' => 'LLL:EXT:widget_mirror/Resources/Private/Language/backend.xlf:sys_file_storage.widget_mirror.max_storage_size.description',
        'displayCond' => 'FIELD:driver:=:Local',
        'config' => [
            'type' => 'input',
            'size' => 20,
            'eval' => 'trim',
            'default' => '1GB',
        ],
    ]
];

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('sys_file_storage', $tempColumns);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'sys_file_storage',
    '--div--;LLL:EXT:widget_mirror/Resources/Private/Language/backend.xlf:sys_file_storage.widget_mirror,'
    . 'tx_widget_mirror_max_size',
    '',
    'after:processingfolder'
);
