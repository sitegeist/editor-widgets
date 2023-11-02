<?php

declare(strict_types=1);
namespace Sitegeist\WidgetMirror;

use Sitegeist\WidgetMirror\Widgets\BrokenLinksWidget;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Linkvalidator\LinkAnalyzer;
use TYPO3\CMS\Linkvalidator\Repository\BrokenLinkRepository;
use TYPO3\CMS\Linkvalidator\Repository\PagesRepository;

return function (ContainerConfigurator $configurator, ContainerBuilder $containerBuilder) {
    $services = $configurator->services();

    if ($containerBuilder->hasDefinition(LinkAnalyzer::class)) {
        $services->set('widgets.dashboard.widget.exampleWidget')
            ->class(BrokenLinksWidget::class)
            ->arg('$view', new Reference('dashboard.views.widget'))
            ->arg('$connectionPool', new Reference(ConnectionPool::class))
            ->arg('$brokenLinkRepository', new Reference(BrokenLinkRepository::class))
            ->arg('$pagesRepository', new Reference(PagesRepository::class))
            ->tag('dashboard.widget', [
                'identifier' => 'sitegeist.widget_mirror.brokenLinks',
                'groupNames' => 'systemInfo',
                'title' => 'LLL:EXT:widget_mirror/Resources/Private/Language/backend.xlf:widgets.brokenLinks.title',
                'description' => 'LLL:EXT:widget_mirror/Resources/Private/Language/backend.xlf:widgets.brokenLinks.description',
                'iconIdentifier' => 'widget-mirror',
                'height' => 'large',
                'width' => 'medium',
                'additionalCssClasses' => 'sitegeist-widget-mirror',
            ]);
    }
};
