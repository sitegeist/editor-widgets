<?php

namespace Sitegeist\WidgetMirror\Widgets;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;

class StorageSizeWidget implements WidgetInterface
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
        $this->configuration = $configuration;
        $this->view = $view;
        $this->languageServiceFactory = $languageServiceFactory;
    }

    public function renderWidgetContent(): string
    {
        $this->view->setTemplate('Widget/StorageSizeWidget');
        $this->view->assignMultiple([
            'storageData' => $this->getDefaultStorageData(),
            'configuration' => $this->configuration,
        ]);

        return $this->view->render();
    }

    protected function getDefaultStorageData()
    {
        $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
        $storage = $storageRepository->getDefaultStorage();
        $data = [
            'name' => $storage->getName(),
            'usage' => ''
        ];
        $maxSize = getenv('FILEADMIN_MAX_SIZE');

        if ($storage->getDriverType() == 'Local' && !empty($maxSize)) {
            $path = Environment::getPublicPath() . $storage->getRootLevelFolder()->getPublicUrl();
            if (is_readable($path)) {
                $data['usage'] = (disk_total_space($path) - disk_free_space($path)) / ($maxSize / 100);
            }
        }

        return $data;
    }
}
