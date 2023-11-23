<?php
$EM_CONF['editor_widgets'] = [
    'title' => 'Editor Widgets',
    'description' => 'Collection of useful dashboard widgets focused on editors',
    'category' => 'backend',
    'author' => 'Benjamin Tammling',
    'author_email' => 'extensions@sitegeist.de',
    'author_company' => 'sitegeist media solutions GmbH',
    'state' => 'beta',
    'uploadfolder' => false,
    'clearCacheOnLoad' => true,
    'version' => '1.0.1',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-12.4.99',
            'php' => '8.1.0-8.2.99'
        ],
        'conflicts' => [
        ],
        'suggests' => [
            'typo3/cms-linkvalidator' => '*',
        ],
    ],
    'autoload' => [
        'psr-4' => [
            'Sitegeist\\EditorWidgets\\' => 'Classes'
        ]
    ],
];
