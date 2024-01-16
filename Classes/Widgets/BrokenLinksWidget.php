<?php

namespace Sitegeist\EditorWidgets\Widgets;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Linkvalidator\Linktype\AbstractLinktype;
use TYPO3\CMS\Linkvalidator\Repository\BrokenLinkRepository;
use TYPO3\CMS\Linkvalidator\Repository\PagesRepository;

class BrokenLinksWidget implements WidgetInterface
{
    const PAGE_ID = 0;

    private $clause = null;

    public function __construct(
        private BrokenLinkRepository $brokenLinkRepository,
        private ConnectionPool $connectionPool,
        private PagesRepository $pagesRepository,
        private StandaloneView $view,
        private WidgetConfigurationInterface $configuration,
        private readonly array $options = []
    )
    {}

    public function renderWidgetContent(): string
    {
        $languageServiceFactory = GeneralUtility::makeInstance(LanguageServiceFactory::class);
        $GLOBALS['LANG'] = $languageServiceFactory->createFromUserPreferences($GLOBALS['BE_USER']);
        $GLOBALS['LANG']->includeLLFile('EXT:linkvalidator/Resources/Private/Language/Module/locallang.xlf');

        $brokenLinks = $this->brokenLinkRepository->getAllBrokenLinksForPages(
            $this->getPageList(),
            ['db', 'file', 'external'],
            $this->getSearchFields()
        );

        foreach ($brokenLinks as $key => &$brokenLink) {
            if ($GLOBALS['TCA'][$brokenLink['table_name']]['ctrl']['versioningWS']) {
                $recordWorkspaceId = $this->getRecordWorkspaceId($brokenLink['table_name'], $brokenLink['record_uid']);
                $brokenLink['isWorkspaceRecord'] = $recordWorkspaceId > 0;

                if ($brokenLink['isWorkspaceRecord']
                    && $recordWorkspaceId != $this->getBackendUser()->workspace
                ) {
                    unset($brokenLinks[$key]);
                    continue;
                }
            }

            /** @var AbstractLinktype $linkType */
            $linkType = GeneralUtility::makeInstance($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'][$brokenLink['link_type']]);
            $brokenLink['path'] = BackendUtility::getRecordPath($brokenLink['record_pid'], $this->getClause(), 0);
            $brokenLink['linkTarget'] = $linkType->getBrokenUrl($brokenLink);
            $brokenLink['linkMessage'] = $this->getLinkMessage($brokenLink, $linkType);
        }

        $this->view->assignMultiple([
            'brokenLinks' => $brokenLinks,
            'configuration' => $this->configuration,
            'dateFormat' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] . ' ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'],
        ]);
        $this->view->setTemplate('Widget/BrokenLinksWidget');

        return $this->view->render();
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLinkMessage(array $brokenLink, AbstractLinktype $linkType): string
    {
        if ($brokenLink['url_response']['valid'] ?? false) {
            return '';
        }

        return $linkType->getErrorMessage($brokenLink['url_response']['errorParams'] ?? ['errorType' => 'unknown', 'exception' => 'Invalid response']);
    }

    protected function getPageList(): array
    {
        $modTS = BackendUtility::getPagesTSconfig(self::PAGE_ID)['mod.']['linkvalidator.'] ?? [];
        $checkForHiddenPages = (bool)$modTS['checkhidden'];
        $pageList = $this->pagesRepository->getAllSubpagesForPage(
            self::PAGE_ID,
            999,
            $this->getClause(),
            $checkForHiddenPages
        );
        // Always add the current page, because we are just displaying the results
        $pageList[] = self::PAGE_ID;
        $pageTranslations = $this->pagesRepository->getTranslationForPage(
            self::PAGE_ID,
            $this->getClause(),
            $checkForHiddenPages
        );
        return array_merge($pageList, $pageTranslations);
    }

    protected function getSearchFields(): array
    {
        $searchFieldMapping = BackendUtility::getPagesTSconfig(self::PAGE_ID)['mod.']['linkvalidator.']['searchFields.'] ?? [];
        foreach ($searchFieldMapping as $table => $searchFields) {
            $searchFieldMapping[$table] = GeneralUtility::trimExplode(',', $searchFields);
        }
        return $searchFieldMapping;
    }

    protected function getClause(): string
    {
        if ($this->clause) {
            return $this->clause;
        }

        $pageClause = $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW);

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $workspaceRestriction = GeneralUtility::makeInstance(
            WorkspaceRestriction::class,
            GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('workspace', 'id')
        );
        $workspaceClause = $workspaceRestriction->buildExpression(['pages' => 'pages'], $queryBuilder->expr());

        $this->clause = $pageClause . ' AND ' . $workspaceClause;
        return $this->clause;
    }

    protected function getRecordWorkspaceId(string $tableName, int $recordUid): bool
    {
        return (int) BackendUtility::getRecord(
            $tableName,
            $recordUid,
            't3ver_wsid'
        )['t3ver_wsid'];
    }
}
