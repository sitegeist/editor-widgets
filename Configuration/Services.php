<?php

declare(strict_types=1);
namespace Sitegeist\EditorWidgets;

use Sitegeist\EditorWidgets\Widgets\BrokenLinksWidget;
use Sitegeist\EditorWidgets\Widgets\DuplicateFilesWidget;
use Sitegeist\EditorWidgets\Widgets\IndexedSearchStatisticWidget;
use Sitegeist\EditorWidgets\Widgets\LastChangedPagesWidget;
use Sitegeist\EditorWidgets\Widgets\LatestRedirectsWidget;
use Sitegeist\EditorWidgets\Widgets\StorageSizeWidget;
use Sitegeist\EditorWidgets\Widgets\UnusedFilesWidget;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\IndexedSearch\Controller\AdministrationController;
use TYPO3\CMS\Linkvalidator\Repository\BrokenLinkRepository;
use TYPO3\CMS\Redirects\Repository\RedirectRepository;

return function (ContainerConfigurator $configurator) {
    $services = $configurator->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure()
        ->public(false);

    $services->set('cache.editor_widgets.storageSize')
        ->class(FrontendInterface::class)
        ->factory([new Reference(CacheManager::class), 'getCache'])
        ->args(['$identifier' => 'editor_widgets_storage_size']);
    $services->alias(FrontendInterface::class, 'cache.editor_widgets.storageSize');

    $languageFilePath = 'LLL:EXT:editor_widgets/Resources/Private/Language/locallang.xlf';
    $commonTags = [
        'additionalCssClasses' => 'sitegeist-editor-widgets',
        'groupNames' => 'systemInfo',
        'height' => 'medium',
        'iconIdentifier' => 'editor-widgets',
        'width' => 'medium',
    ];

    $services->set(StorageSizeWidget::class)
        ->args(['$cache' => new Reference('cache.editor_widgets.storageSize')])
        ->tag('dashboard.widget', array_merge($commonTags, [
            'title' => $languageFilePath . ':widgets.storageSize.title',
            'description' => $languageFilePath . ':widgets.storageSize.description',
            'width' => 'small',
        ]));

    $services->set(UnusedFilesWidget::class)
        ->tag('dashboard.widget', array_merge($commonTags, [
            'title' => $languageFilePath . ':widgets.unusedFiles.title',
            'description' => $languageFilePath . ':widgets.unusedFiles.description',
        ]));

    $services->set(DuplicateFilesWidget::class)
        ->tag('dashboard.widget', array_merge($commonTags, [
            'title' => $languageFilePath . ':widgets.duplicateFiles.title',
            'description' => $languageFilePath . ':widgets.duplicateFiles.description',
        ]));

    $services->set(LastChangedPagesWidget::class)
        ->tag('dashboard.widget', array_merge($commonTags, [
            'title' => $languageFilePath . ':widgets.lastChangedPages.title',
            'description' => $languageFilePath . ':widgets.lastChangedPages.description',
        ]));

    if (class_exists(BrokenLinkRepository::class)) {
        $services->set(BrokenLinksWidget::class)
            ->tag('dashboard.widget', array_merge($commonTags, [
                'title' => $languageFilePath . ':widgets.brokenLinks.title',
                'description' => $languageFilePath . ':widgets.brokenLinks.description',
                'height' => 'large',
                'width' => 'large',
            ]));
    }

    if (class_exists(RedirectRepository::class)) {
        $services->set(LatestRedirectsWidget::class)
            ->tag('dashboard.widget', array_merge($commonTags, [
                'title' => $languageFilePath . ':widgets.latestRedirects.title',
                'description' => $languageFilePath . ':widgets.latestRedirects.description',
            ]));
    }

    if (class_exists(AdministrationController::class)) {
        $services->set(IndexedSearchStatisticWidget::class)
            ->tag('dashboard.widget', array_merge($commonTags, [
                'title' => $languageFilePath . ':widgets.indexedSearchStatistics.title',
                'description' => $languageFilePath . ':widgets.indexedSearchStatistics.description',
            ]));
    }
};
