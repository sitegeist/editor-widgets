<?php

return [
    'ctrl' => [
        'title' => 'Broken links',
        'label' => 'linkvalidator_link',
        'iconfile' => 'EXT:editor_widgets/Resources/Public/Icons/Extension.svg',
        'hideTable' => true,
    ],
    'columns' => [
        'linkvalidator_link' => [
            'config' => [
                'type' => 'input',
            ],
        ],
        'suppressed' => [
            'config' => [
                'type' => 'check',
            ],
        ],
    ],
];
