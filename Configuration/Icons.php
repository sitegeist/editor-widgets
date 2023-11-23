<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'editor-widgets' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:editor_widgets/Resources/Public/Icons/Extension.svg',
    ],
];
