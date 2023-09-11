<?php

namespace Sitegeist\WidgetMirror\Widgets;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;

class DuplicateFilesWidget implements WidgetInterface
{
    public function __construct(
        private ?ConnectionPool $connectionPool = null,
        private ?StandaloneView $view = null,
        private ?WidgetConfigurationInterface $configuration = null
    )
    {}

    public function renderWidgetContent(): string
    {
        $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
        $queryBuilder = $this->connectionPool->getConnectionForTable('sys_file')->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();

        $duplicates = $queryBuilder
            ->selectLiteral('GROUP_CONCAT(uid) as uids', 'sha1', 'count(*) as counting')
            ->from('sys_file')
            ->addOrderBy('counting', 'desc')
            ->where('name != "index.html"')
            ->groupBy('sha1')
            ->having('counting > 1')
            ->execute()
            ->fetchAllAssociative();

        foreach ($duplicates as &$duplicate) {
            $duplicate['files'] = array_map(
                function ($uid) use ($resourceFactory) {
                    $file = $resourceFactory->getFileObject($uid);
                    return [
                        'file' => $file,
                        'referenceCount' => BackendUtility::referenceCount('sys_file', $file->getUid()),
                    ];
                },
                GeneralUtility::trimExplode(',', $duplicate['uids'])
            );

        }

        $this->view->setTemplate('Widget/DuplicateFilesWidget');
        $this->view->assignMultiple([
            'duplicates' => $duplicates,
            'configuration' => $this->configuration
        ]);

        return $this->view->render();
    }
}
