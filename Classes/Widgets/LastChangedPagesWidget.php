<?php

namespace Sitegeist\EditorWidgets\Widgets;

use TYPO3\CMS\Backend\History\RecordHistory;
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Dashboard\Widgets\AdditionalCssInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;

class LastChangedPagesWidget implements WidgetInterface, AdditionalCssInterface
{
    public function __construct(
        private ConnectionPool $connectionPool,
        private StandaloneView $view,
        private WidgetConfigurationInterface $configuration,
        private array $userNames = [],
        private readonly array $options = []
    )
    {
        $this->userNames = BackendUtility::getUserNames();
    }

    public function renderWidgetContent(): string
    {
        $queryBuilder = $this->connectionPool->getConnectionForTable('pages')->createQueryBuilder();
        $queryBuilder->getRestrictions()
            ->removeByType(HiddenRestriction::class)
            ->add(
                GeneralUtility::makeInstance(
                    WorkspaceRestriction::class,
                    GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('workspace', 'id')
                )
            );

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

            $page['userName'] = $this->getUserNameOfLatestChange($page['uid']);
        }
        $this->view->setTemplate('Widget/LastChangedPagesWidget');
        $this->view->assignMultiple([
            'pages' => $pages,
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

    private function getUserNameOfLatestChange(int $pageUid): string
    {
        $history = GeneralUtility::makeInstance(RecordHistory::class, 'pages:' . $pageUid);
        $history->setMaxSteps(1);
        $latestChange = array_shift($history->getChangeLog());

        if (!isset($latestChange['userid'])) {
            return '';
        }

        return $this->userNames[$latestChange['userid']]['username'] ?? '';
    }
}
