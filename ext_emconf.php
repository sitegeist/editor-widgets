<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Editor Widgets',
    'description' => 'Collection of useful dashboard widgets focused on editors',
    'category' => 'backend',
    'author' => 'Ulrich Mathes, Benjamin Tammling',
    'author_email' => 'mathes@sitegeist.de, benjamin.tammling@sitegeist.de',
    'author_company' => 'sitegeist media solutions GmbH',
    'state' => 'stable',
    'version' => '',
    'uploadfolder' => false,
    'clearCacheOnLoad' => true,
    'constraints' => [
        'depends' => [
            'typo3/cms-dashboard' => '12.4.0-13.9.99',
            'php' => '8.1.0-8.3.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
            'typo3/cms-linkvalidator' => '*',
            'typo3/cms-redirects' => '*',
        ],
    ],
    'autoload' => [
        'psr-4' => [
            'Sitegeist\\EditorWidgets\\' => 'Classes',
        ],
    ],
];
