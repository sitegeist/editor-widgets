<?php

namespace Sitegeist\EditorWidgets\Widgets;

use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Dashboard\Widgets\AdditionalCssInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Fetches many sys_history entries and finds it's related page uids.
 * The latest pages are fetched respecting backend user access.
 */
class LastChangedPagesWidget implements WidgetInterface, AdditionalCssInterface
{
    const NUM_ENTRIES = 10;
    const NUM_FETCH_SYS_HISTORY_ENTRIES = 1000;

    public function __construct(
        private ConnectionPool $connectionPool,
        private StandaloneView $view,
        private WidgetConfigurationInterface $configuration,
        private array $userNames = [],
        private readonly array $options = []
    )
    {
    }

    public function renderWidgetContent(): string
    {
        $this->userNames = BackendUtility::getUserNames();

        $workspaceRestriction = GeneralUtility::makeInstance(
            WorkspaceRestriction::class,
            GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('workspace', 'id')
        );

        $queryBuilder = $this->connectionPool->getConnectionForTable('sys_history')->createQueryBuilder();
        $queryBuilder->getRestrictions()->add($workspaceRestriction);

        $queryBuilderTtContent = $this->connectionPool->getConnectionForTable('tt_content')->createQueryBuilder();
        $queryBuilderTtContent->getRestrictions()->removeAll();

        $queryBuilderPages = $this->connectionPool->getConnectionForTable('pages')->createQueryBuilder();
        $queryBuilderPages->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add($workspaceRestriction);

        $history = $queryBuilder
            ->select('tablename', 'recuid', 'tstamp', 'userid')
            ->from('sys_history')
            ->where($queryBuilder->expr()->in('tablename', [
                $queryBuilder->createNamedParameter('pages'),
                $queryBuilder->createNamedParameter('tt_content'),
            ]))
            ->addOrderBy('tstamp', 'desc')
            ->setMaxResults(self::NUM_FETCH_SYS_HISTORY_ENTRIES)
            ->execute()
            ->fetchAllAssociative();


        $latestPages = [];

        foreach ($history as $historyEntry) {
            if ($historyEntry['tablename'] == 'tt_content') {
                $pid = $queryBuilderTtContent
                    ->select('pid')
                    ->from('tt_content')
                    ->where($queryBuilderTtContent->expr()->eq('uid', $queryBuilderTtContent->createNamedParameter($historyEntry['recuid'])))
                    ->execute()
                    ->fetchOne();
            } else {
                $pid = $historyEntry['recuid'];
            }

            if (isset($latestPages[$pid])) {
                continue;
            }

            // deleted pages don't show up in list
            $page = $queryBuilderPages
                ->select('*')
                ->from('pages')
                ->where($queryBuilderPages->expr()->eq('uid', $queryBuilderPages->createNamedParameter($pid)))
                ->andWhere($GLOBALS['BE_USER']->getPagePermsClause(1))
                ->execute()
                ->fetchAssociative();

            if (!$page) {
                continue;
            }

            $latestPages[$pid] = $page;
            $latestPages[$pid]['history'] = $historyEntry;

            if (count($latestPages) == self::NUM_ENTRIES) {
                break;
            }
        }

        foreach ($latestPages as $pageId => &$page) {
            $rootlineUtility = GeneralUtility::makeInstance(RootlineUtility::class, $pageId);
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

            $page['viewLink'] = (string)PreviewUriBuilder::create($pageId)
                ->withRootLine(BackendUtility::BEgetRootLine($pageId))
                ->buildUri();

            $page['history']['userName'] = $this->userNames[$page['history']['userid']]['username'] ?? '';
        }

        $this->view->setTemplate('Widget/LastChangedPagesWidget');
        $this->view->assignMultiple([
            'pages' => $latestPages,
            'configuration' => $this->configuration,
            'dateFormat' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] . ' ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm']
        ]);

        return $this->view->render();
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
