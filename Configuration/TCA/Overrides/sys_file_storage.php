<?php

declare(strict_types=1);

defined('TYPO3') || die();

$tempColumns = [
    'tx_editor_widgets_max_size' => [
        'label' => 'LLL:EXT:editor_widgets/Resources/Private/Language/backend.xlf:sys_file_storage.editor_widgets.max_storage_size',
        'description' => 'LLL:EXT:editor_widgets/Resources/Private/Language/backend.xlf:sys_file_storage.editor_widgets.max_storage_size.description',
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
    '--div--;LLL:EXT:editor_widgets/Resources/Private/Language/backend.xlf:sys_file_storage.editor_widgets,'
    . 'tx_editor_widgets_max_size',
    '',
    'after:processingfolder'
);
