<?php

namespace Sitegeist\EditorWidgets\Widgets;

use Sitegeist\EditorWidgets\Traits\RequestAwareTrait;
use Sitegeist\EditorWidgets\Traits\WidgetTrait;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Dashboard\Widgets\AdditionalCssInterface;
use TYPO3\CMS\Dashboard\Widgets\RequestAwareWidgetInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;

final class UnusedFilesWidget implements WidgetInterface, RequestAwareWidgetInterface, AdditionalCssInterface
{
    use RequestAwareTrait;
    use WidgetTrait;

    public function __construct(
        private readonly BackendViewFactory $backendViewFactory,
        private readonly ConnectionPool $connectionPool,
        private readonly ResourceFactory $resourceFactory,
        private readonly WidgetConfigurationInterface $configuration,
        private readonly array $options = []
    ) {
    }

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
            ->where(
                'sr.hash IS NULL',
                'sys_file.missing = 0',
                'sys_file.storage > 0',
                'sys_file.identifier NOT LIKE "%_recycler_%"',
                'sys_file.identifier NOT LIKE "/user_upload/index.html"'
            )
            ->setMaxResults(10)
            ->executeQuery()
            ->fetchAllAssociative();

        foreach ($files as &$file) {
            $file = $this->resourceFactory->getFileObject($file['uid']);
            try {
                $file->getParentFolder();
            } catch (\Throwable $th) {
                $file->setMissing(1);
            }
        }

        $view = $this->backendViewFactory->create($this->request, ['sitegeist/editor-widgets']);
        $view->assignMultiple([
            'files' => $files,
            'configuration' => $this->configuration,
        ]);

        return $view->render('UnusedFilesWidget');
    }

    public function getCssFiles(): array
    {
        return [
            'EXT:editor_widgets/Resources/Public/Css/backend.css',
        ];
    }
}
