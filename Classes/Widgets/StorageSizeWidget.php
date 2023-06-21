<?php

namespace Sitegeist\WidgetMirror\Widgets;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Dashboard\WidgetApi;
use TYPO3\CMS\Dashboard\Widgets\AdditionalCssInterface;
use TYPO3\CMS\Dashboard\Widgets\EventDataInterface;
use TYPO3\CMS\Dashboard\Widgets\JavaScriptInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;

class StorageSizeWidget implements WidgetInterface, EventDataInterface, AdditionalCssInterface, JavaScriptInterface
{
    private const DEFAULT_MAX_SIZE = 214748364800;

    public function __construct(
        private ?WidgetConfigurationInterface $configuration = null,
        private ?StandaloneView $view = null
    )
    {}

    public function renderWidgetContent(): string
    {
        $this->view->setTemplate('Widget/StorageSizeWidget');
        $this->view->assignMultiple([
            'configuration' => $this->configuration,
        ]);

        return $this->view->render();
    }

    public function getEventData(): array
    {
        $languageServiceFactory = GeneralUtility::makeInstance(LanguageServiceFactory::class);
        $languageService = $languageServiceFactory->createFromUserPreferences($GLOBALS['BE_USER']);
        $storageData = $this->getDefaultStorageData();
        return [
            'graphConfig' => [
                'type' => 'pie',
                'options' => [
                    'maintainAspectRatio' => false,
                    'legend' => [
                        'display' => true,
                        'position' => 'bottom',
                    ],
                    'title' => [
                        'display' => true,
                        'text' => $storageData['name']
                    ],
                ],
                'data' => [
                    'labels' => [
                        $languageService->sL('LLL:EXT:widget_mirror/Resources/Private/Language/backend.xlf:widgets.storagesize.chart.used'),
                        $languageService->sL('LLL:EXT:widget_mirror/Resources/Private/Language/backend.xlf:widgets.storagesize.chart.free'),
                    ],
                    'datasets' => [
                        [
                            'backgroundColor' => WidgetApi::getDefaultChartColors(),
                            'data' => [number_format($storageData['usage'], 2, '.', ''), number_format(100 - $storageData['usage'], 2, '.', '')],
                        ],
                    ],
                ]
            ],
        ];
    }

    public function getCssFiles(): array
    {
        return ['EXT:dashboard/Resources/Public/Css/Contrib/chart.css'];
    }

    public function getJavaScriptModuleInstructions(): array
    {
        return [
            JavaScriptModuleInstruction::forRequireJS('TYPO3/CMS/Dashboard/Contrib/chartjs'),
            JavaScriptModuleInstruction::forRequireJS('TYPO3/CMS/Dashboard/ChartInitializer'),
        ];
    }

    protected function getDefaultStorageData()
    {
        $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
        $storage = $storageRepository->getDefaultStorage();
        $data = [
            'name' => $storage->getName(),
            'usage' => ''
        ];

        if (!empty($storage->getStorageRecord()['tx_widget_mirror_max_size'])) {
            $maxSize = $storage->getStorageRecord()['tx_widget_mirror_max_size'];
        } else {
            $maxSize = self::DEFAULT_MAX_SIZE;
        }

        if ($storage->getDriverType() == 'Local' && !empty($maxSize)) {
            $path = Environment::getPublicPath() . $storage->getRootLevelFolder()->getPublicUrl();
            if (is_readable($path)) {
                $data['usage'] = $this->getDirSize($path) / ($maxSize / 100);
            }
        }

        return $data;
    }

    protected function getDirSize($path)
    {
        $fio = popen('/usr/bin/du -sb '.$path, 'r');
        $size = intval(fgets($fio,80));
        pclose($fio);
        return $size;
    }

}
