<?php

namespace Sitegeist\WidgetMirror\Widgets;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Dashboard\Widgets\AdditionalCssInterface;
use TYPO3\CMS\Dashboard\Widgets\EventDataInterface;
use TYPO3\CMS\Dashboard\Widgets\JavaScriptInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;

class StorageSizeWidget implements WidgetInterface, EventDataInterface, AdditionalCssInterface, JavaScriptInterface
{
    /**
     * @var WidgetConfigurationInterface
     */
    private $configuration;

    /**
     * @var StandaloneView
     */
    private $view;

    /**
     * @var LanguageServiceFactory
     */
    private $languageServiceFactory;

    public function __construct(
        WidgetConfigurationInterface $configuration,
        StandaloneView               $view,
        LanguageServiceFactory       $languageServiceFactory
    )
    {
        $this->view = $view;
        $this->languageServiceFactory = $languageServiceFactory;
    }

    public function renderWidgetContent(): string
    {
        $this->view->setTemplate('Widget/StorageSizeWidget');

        return $this->view->render();
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

    public function getEventData(): array
    {
        $languageService = $this->languageServiceFactory->createFromUserPreferences($GLOBALS['BE_USER']);
        $data = $this->getDefaultStorageData();
        $output = [];

        if (!empty($data['used']) && !empty($data['free'])) {
            $output = [
                'graphConfig' => [
                    'type' => 'bar',
                    'options' => [
                        'maintainAspectRatio' => false,
                        'legend' => [
                            'display' => true,
                        ],
                        'responsive' => true,
                        'title' => [
                            'display' => true,
                            'text' => $languageService->sL('LLL:EXT:widget_mirror/Resources/Private/Language/backend.xlf:widgets.storagesize.chart.title')
                        ],
                        'scales' => [
                            'xAxes' => [
                                'stacked' => true
                            ],
                            'yAxes' => [
                                'stacked' => true
                            ]
                        ],
                    ],
                    'data' => [
                        'labels' =>
                            [
                                $data['name'],
                            ],
                        'datasets' => [
                            [
                                'stack' => '1',
                                'label' => $languageService->sL('LLL:EXT:widget_mirror/Resources/Private/Language/backend.xlf:widgets.storagesize.chart.used'),
                                'backgroundColor' => '#e98e9f',
                                'data' => [floor($data['used'] / 1024 / 1024 / 1024)],
                            ],
                            [
                                'stack' => '1',
                                'label' => $languageService->sL('LLL:EXT:widget_mirror/Resources/Private/Language/backend.xlf:widgets.storagesize.chart.free'),
                                'backgroundColor' => '#93bc77',
                                'data' => [floor($data['free'] / 1024 / 1024 / 1024)],
                            ]
                        ],
                    ]
                ]
            ];
        }

        return $output;
    }

    protected function getDefaultStorageData()
    {
        $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
        $storage = $storageRepository->getDefaultStorage();
        $storageData = [];

        $storageData['name'] = $storage->getName();

        if ($storage->getDriverType() == 'Local') {
            $path = Environment::getPublicPath() . $storage->getRootLevelFolder()->getPublicUrl();
            if (is_readable($path)) {
                $storageData['used'] = disk_total_space($path) - disk_free_space($path);
                $storageData['free'] = disk_free_space($path);
            }
        }

        return $storageData;
    }
}
