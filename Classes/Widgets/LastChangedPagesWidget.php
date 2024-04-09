<?php

namespace Sitegeist\EditorWidgets\Widgets;

use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
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
 * Deleted pages don't show up in list.
 */
class LastChangedPagesWidget implements WidgetInterface, AdditionalCssInterface
{
    const NUM_ENTRIES = 10;
    const NUM_FETCH_SYS_HISTORY_ENTRIES = 1000;

    private ?QueryBuilder $queryBuilderSysHistory = null;
    private ?QueryBuilder $queryBuilderTtContent = null;
    private ?QueryBuilder $queryBuilderPages = null;

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
        $this->initializeQueryBuilders();

        $history = $this->getSysHistory();

        $latestPages = [];

        foreach ($history as $historyEntry) {
            if ($historyEntry['tablename'] == 'tt_content') {
                $pid = $this->getPidFromTtContent($historyEntry['recuid']);
            } else {
                $pid = $historyEntry['recuid'];
            }

            if(!$pid) {
                continue;
            }

            if (isset($latestPages[$pid])) {
                continue;
            }

            $page = $this->getPage($pid);

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
            $page['rootline'] = $this->getRootline($pageId);

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

    private function getSysHistory(): array
    {
        return $this->queryBuilderSysHistory
            ->select('tablename', 'recuid', 'tstamp', 'userid')
            ->from('sys_history')
            ->where($this->queryBuilderSysHistory->expr()->in('tablename', [
                $this->queryBuilderSysHistory->createNamedParameter('pages'),
                $this->queryBuilderSysHistory->createNamedParameter('tt_content'),
            ]))
            ->addOrderBy('tstamp', 'desc')
            ->setMaxResults(self::NUM_FETCH_SYS_HISTORY_ENTRIES)
            ->execute()
            ->fetchAllAssociative() ?? [];
    }

    private function getPidFromTtContent(int $uid):? int
    {
        $pid = $this->queryBuilderTtContent
            ->select('pid')
            ->from('tt_content')
            ->where($this->queryBuilderTtContent->expr()->eq('uid', $this->queryBuilderTtContent->createNamedParameter($uid)))
            ->execute()
            ->fetchOne();

        return $pid ? (int) $pid : null;
    }

    private function getPage(int $pageId):? array
    {
        $page = $this->queryBuilderPages
            ->select('*')
            ->from('pages')
            ->where($this->queryBuilderPages->expr()->eq('uid', $this->queryBuilderPages->createNamedParameter($pageId)))
            ->andWhere($GLOBALS['BE_USER']->getPagePermsClause(1))
            ->execute()
            ->fetchAssociative();

        return $page ?: null;
    }

    private function getRootLine(int $pageId): string
    {
        $rootlineUtility = GeneralUtility::makeInstance(RootlineUtility::class, $pageId);
        return implode(
            ' / ',
            array_slice(
                array_map(function ($page) {
                        return $page['title'];
                    }, array_reverse($rootlineUtility->get())
                ),
            0,
            -1)
        );
    }

    private function initializeQueryBuilders(): void
    {
        $workspaceRestriction = GeneralUtility::makeInstance(
            WorkspaceRestriction::class,
            GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('workspace', 'id')
        );

        $this->queryBuilderSysHistory = $this->connectionPool->getConnectionForTable('sys_history')->createQueryBuilder();
        $this->queryBuilderSysHistory->getRestrictions()->add($workspaceRestriction);

        $this->queryBuilderTtContent = $this->connectionPool->getConnectionForTable('tt_content')->createQueryBuilder();
        $this->queryBuilderTtContent->getRestrictions()->removeAll();

        $this->queryBuilderPages = $this->connectionPool->getConnectionForTable('pages')->createQueryBuilder();
        $this->queryBuilderPages->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add($workspaceRestriction);
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
