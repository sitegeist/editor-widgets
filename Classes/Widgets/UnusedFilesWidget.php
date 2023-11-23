<?php

namespace Sitegeist\WidgetMirror\Widgets;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;

class UnusedFilesWidget implements WidgetInterface
{
    public function __construct(
        private ConnectionPool $connectionPool,
        private ResourceFactory $resourceFactory,
        private StandaloneView $view,
        private WidgetConfigurationInterface $configuration,
        private readonly array $options = []
    )
    {}

    public function renderWidgetContent(): string
    {
        $queryBuilder = $this->connectionPool->getConnectionForTable('sys_file')->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();

        $files = $queryBuilder
            ->select('sys_file.uid')
            ->from('sys_file')
            ->leftJoin('sys_file', 'sys_refindex', 'sr', 'sr.ref_uid = sys_file.uid AND sr.tablename != "sys_file_metadata" AND sr.ref_table = "sys_file"')
            ->orderBy('sys_file.size', 'desc')
            ->addOrderBy('sys_file.identifier', 'desc')
            ->where('sys_file.missing = 0 AND sys_file.storage > 0 AND sys_file.identifier NOT LIKE "%_recycler_%" AND sr.hash IS NULL')
            ->setMaxResults(10)
            ->execute()
            ->fetchAllAssociative();

        foreach ($files as &$file) {
            $file = $this->resourceFactory->getFileObject($file['uid']);
            try {
                $file->getParentFolder();
            } catch (\Throwable $th) {
                $file->setMissing(1);
            }
        }

        $this->view->setTemplate('Widget/UnusedFilesWidget');
        $this->view->assignMultiple([
            'files' => $files,
            'configuration' => $this->configuration
        ]);

        return $this->view->render();
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
