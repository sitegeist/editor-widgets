<?php

declare(strict_types=1);
namespace Sitegeist\EditorWidgets;

use Sitegeist\EditorWidgets\Widgets\BrokenLinksWidget;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Linkvalidator\Repository\BrokenLinkRepository;
use TYPO3\CMS\Linkvalidator\Repository\PagesRepository;

return function (ContainerConfigurator $configurator, ContainerBuilder $containerBuilder) {
    $services = $configurator->services();

    if ($containerBuilder->hasDefinition(BrokenLinkRepository::class)) {
        $services->set('dashboard.widget.sitegeist.editor_widgets.brokenLinks:')
            ->class(BrokenLinksWidget::class)
            ->arg('$view', new Reference('dashboard.views.widget'))
            ->arg('$connectionPool', new Reference(ConnectionPool::class))
            ->arg('$brokenLinkRepository', new Reference(BrokenLinkRepository::class))
            ->arg('$pagesRepository', new Reference(PagesRepository::class))
            ->tag('dashboard.widget', [
                'identifier' => 'sitegeist.editor_widgets.brokenLinks',
                'groupNames' => 'systemInfo',
                'title' => 'LLL:EXT:editor_widgets/Resources/Private/Language/backend.xlf:widgets.brokenLinks.title',
                'description' => 'LLL:EXT:editor_widgets/Resources/Private/Language/backend.xlf:widgets.brokenLinks.description',
                'iconIdentifier' => 'editor-widgets',
                'height' => 'large',
                'width' => 'large',
                'additionalCssClasses' => 'sitegeist-editor-widgets',
            ]);
    }
};
