<?php

declare(strict_types=1);
namespace Sitegeist\EditorWidgets;

use Sitegeist\EditorWidgets\Widgets\BrokenLinksWidget;
use Sitegeist\EditorWidgets\Widgets\LatestRedirectsWidget;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Linkvalidator\Linktype\LinktypeRegistry;
use TYPO3\CMS\Linkvalidator\Repository\BrokenLinkRepository;
use TYPO3\CMS\Linkvalidator\Repository\PagesRepository;
use TYPO3\CMS\Redirects\Repository\RedirectRepository;

return function (ContainerConfigurator $configurator, ContainerBuilder $containerBuilder) {
    $services = $configurator->services();

    if ($containerBuilder->hasDefinition(BrokenLinkRepository::class)) {
        $services->set('dashboard.widget.sitegeist.editor_widgets.brokenLinks:')
            ->class(BrokenLinksWidget::class)
            ->arg('$backendViewFactory', new Reference(BackendViewFactory::class))
            ->arg('$brokenLinkRepository', new Reference(BrokenLinkRepository::class))
            ->arg('$connectionPool', new Reference(ConnectionPool::class))
            ->arg('$linktypeRegistry', new Reference(LinktypeRegistry::class))
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
            ]
        );
    }

    if ($containerBuilder->hasDefinition(RedirectRepository::class)) {
        $services->set('dashboard.widget.sitegeist.editor_widgets.latestRedirects:')
            ->class(LatestRedirectsWidget::class)
            ->arg('$backendViewFactory', new Reference(BackendViewFactory::class))
            ->arg('$connectionPool', new Reference(ConnectionPool::class))
            ->tag('dashboard.widget', [
                'identifier' => 'sitegeist.editor_widgets.latestRedirects',
                'groupNames' => 'systemInfo',
                'title' => 'LLL:EXT:editor_widgets/Resources/Private/Language/backend.xlf:widgets.latestRedirects.title',
                'description' => 'LLL:EXT:editor_widgets/Resources/Private/Language/backend.xlf:widgets.latestRedirects.description',
                'iconIdentifier' => 'editor-widgets',
                'height' => 'medium',
                'width' => 'medium',
                'additionalCssClasses' => 'sitegeist-editor-widgets',
            ]
        );
    }
};
