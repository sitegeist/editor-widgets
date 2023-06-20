<?php

namespace Sitegeist\WidgetMirror\Widgets;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;

class LastChangedPagesWidget implements WidgetInterface
{
    public function __construct(
        private ?WidgetConfigurationInterface $configuration = null,
        private ?StandaloneView $view = null,
        private ?ConnectionPool $connectionPool = null
    )
    {}

    public function renderWidgetContent(): string
    {
        $queryBuilder = $this->connectionPool->getConnectionForTable('sys_file')->createQueryBuilder();

        $pages = $queryBuilder
            ->select('uid', 'tstamp')
            ->from('pages')
            ->addOrderBy('tstamp', 'desc')
            ->where($GLOBALS['BE_USER']->getPagePermsClause(1))
            ->setMaxResults(10)
            ->execute()
            ->fetchAllAssociative();

        foreach ($pages as &$page) {
            $rootlineUtility = GeneralUtility::makeInstance(RootlineUtility::class, $page['uid']);
            $page['rootline'] = implode(
                '/',
                array_slice(
                    array_map(function ($page) {
                            return $page['title'];
                        }, array_reverse($rootlineUtility->get())
                    ),
                1)
            );
        }
        $this->view->setTemplate('Widget/LastChangedPagesWidget');
        $this->view->assignMultiple([
            'pages' => $pages,
            'configuration' => $this->configuration
        ]);

        return $this->view->render();
    }
}
