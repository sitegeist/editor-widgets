<?php

namespace Sitegeist\EditorWidgets\Widgets;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Dashboard\Widgets\AdditionalCssInterface;
use TYPO3\CMS\Dashboard\Widgets\RequestAwareWidgetInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;

class DuplicateFilesWidget implements WidgetInterface, RequestAwareWidgetInterface, AdditionalCssInterface
{
    private ServerRequestInterface $request;
    
    public function __construct(
        private readonly BackendViewFactory $backendViewFactory,
        private ConnectionPool $connectionPool,
        private ResourceFactory $resourceFactory,
        private WidgetConfigurationInterface $configuration,
        private readonly array $options = []
    )
    {}
    
    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    public function renderWidgetContent(): string
    {
        $queryBuilder = $this->connectionPool->getConnectionForTable('sys_file')->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();

        $duplicates = $queryBuilder
            ->selectLiteral('GROUP_CONCAT(uid) as uids', 'sha1', 'count(*) as counting')
            ->from('sys_file')
            ->where('missing = 0')
            ->andWhere('storage > 0')
            ->andWhere('name != "index.html"')
            ->andWhere('identifier NOT LIKE "%_recycler_%"')
            ->orderBy('counting', 'desc')
            ->addOrderBy('size', 'desc')
            ->groupBy('sha1')
            ->having('counting > 1')
            ->setMaxResults(200)
            ->executeQuery()
            ->fetchAllAssociative();

        foreach ($duplicates as &$duplicate) {
            $duplicate['files'] = array_map(
                function ($uid) {
                    $file = $this->resourceFactory->getFileObject($uid);

                    try {
                        $file->getParentFolder();
                    } catch (\Throwable $th) {
                        $file->setMissing(1);
                    }

                    return [
                        'file' => $file,
                        'referenceCount' => BackendUtility::referenceCount('sys_file', $file->getUid()),
                        'isImage' => $file->isImage(),
                    ];
                },
                GeneralUtility::trimExplode(',', $duplicate['uids'])
            );
        }

        $view = $this->backendViewFactory->create($this->request, ['sitegeist/editor-widgets']);
        $view->assignMultiple([
            'duplicates' => $duplicates,
            'configuration' => $this->configuration
        ]);

        return $view->render('DuplicateFilesWidget');
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getCssFiles(): array
    {
       return [
           'EXT:editor_widgets/Resources/Public/Css/backend.css',
       ];
    }
}
