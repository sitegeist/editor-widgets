<?php

namespace Sitegeist\WidgetMirror\Widgets;

use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
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
        $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);

        $pages = $queryBuilder
            ->select('*')
            ->from('pages')
            ->addOrderBy('tstamp', 'desc')
            ->where($GLOBALS['BE_USER']->getPagePermsClause(1))
            ->setMaxResults(10)
            ->execute()
            ->fetchAllAssociative();

        foreach ($pages as &$page) {
            $rootlineUtility = GeneralUtility::makeInstance(RootlineUtility::class, $page['uid']);
            $page['rootline'] = implode(
                ' / ',
                array_slice(
                    array_map(function ($page) {
                            return $page['title'];
                        }, array_reverse($rootlineUtility->get())
                    ),
                0,
                -1)
            );

            $page['viewLink'] = (string)PreviewUriBuilder::create($page['uid'])
                ->withRootLine(BackendUtility::BEgetRootLine($page['uid']))
                ->buildUri();
        }
        $this->view->setTemplate('Widget/LastChangedPagesWidget');
        $this->view->assignMultiple([
            'pages' => $pages,
            'configuration' => $this->configuration,
            'dateFormat' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] . ' ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm']
        ]);

        return $this->view->render();
    }
}
