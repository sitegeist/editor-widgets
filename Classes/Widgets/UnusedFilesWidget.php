<?php

namespace Sitegeist\WidgetMirror\Widgets;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;

class UnusedFilesWidget implements WidgetInterface
{
    public function __construct(
        private ?WidgetConfigurationInterface $configuration = null,
        private ?StandaloneView $view = null,
        private ?ConnectionPool $connectionPool = null,
        private readonly array $options = []
    )
    {}

    public function renderWidgetContent(): string
    {
        $queryBuilder = $this->connectionPool->getConnectionForTable('sys_file')->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();

        $files = $queryBuilder
            ->select('sys_file.uid', 'sys_file.identifier', 'sys_file.size')
            ->from('sys_file')
            ->leftJoin('sys_file', 'sys_refindex', 'sr', 'sr.ref_uid = sys_file.uid AND sr.tablename != "sys_file_metadata" AND sr.ref_table = "sys_file"')
            ->addOrderBy('sys_file.size', 'desc')
            ->where('sr.hash IS NULL')
            ->setMaxResults(10)
            ->execute()
            ->fetchAllAssociative();

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
