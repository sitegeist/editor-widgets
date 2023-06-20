<?php

namespace Sitegeist\WidgetMirror\Widgets;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;

class StorageSizeWidget implements WidgetInterface
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
