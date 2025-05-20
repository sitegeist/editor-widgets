<?php

namespace Sitegeist\EditorWidgets\Widgets;

use Sitegeist\EditorWidgets\Traits\RequestAwareTrait;
use Sitegeist\EditorWidgets\Traits\WidgetTrait;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Dashboard\Widgets\AdditionalCssInterface;
use TYPO3\CMS\Dashboard\Widgets\JavaScriptInterface;
use TYPO3\CMS\Dashboard\Widgets\RequestAwareWidgetInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;
use TYPO3\CMS\Linkvalidator\Linktype\AbstractLinktype;
use TYPO3\CMS\Linkvalidator\Linktype\LinktypeRegistry;
use TYPO3\CMS\Linkvalidator\Repository\BrokenLinkRepository;
use TYPO3\CMS\Linkvalidator\Repository\PagesRepository;

final class BrokenLinksWidget implements WidgetInterface, RequestAwareWidgetInterface, AdditionalCssInterface, JavaScriptInterface
{
    use RequestAwareTrait;
    use WidgetTrait;

    const PAGE_ID = 0;
    const PERSISTENT_TABLE = 'tx_editor_widgets_broken_link';

    private $clause;

    public function __construct(
        private readonly BackendViewFactory $backendViewFactory,
        private readonly ConnectionPool $connectionPool,
        private readonly WidgetConfigurationInterface $configuration,
        private readonly ?BrokenLinkRepository $brokenLinkRepository = null,
        private readonly ?LinktypeRegistry $linktypeRegistry = null,
        private readonly ?PagesRepository $pagesRepository = null,
        private readonly array $options = []
    ) {
    }

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

        $view = $this->backendViewFactory->create($this->request, ['sitegeist/editor-widgets']);
        $persistentBrokenLinks = $this->getPersistentBrokenLinks();
        $enabledBrokenLinks = [];
        $suppressedBrokenLinks = [];

        foreach ($brokenLinks as $key => &$brokenLink) {
            if ($GLOBALS['TCA'][$brokenLink['table_name']]['ctrl']['versioningWS']) {
                $recordWorkspaceId = $this->getRecordWorkspaceId($brokenLink['table_name'], $brokenLink['record_uid']);
                $brokenLink['isWorkspaceRecord'] = $recordWorkspaceId > 0;

                if ($brokenLink['isWorkspaceRecord']
                    && $recordWorkspaceId != $this->getBackendUser()->workspace
                ) {
                    continue;
                }
            }

            /** @var AbstractLinktype $linkType */
            $linkType = $this->linktypeRegistry->getLinktype($brokenLink['link_type'] ?? '');
            $brokenLink['path'] = BackendUtility::getRecordPath($brokenLink['record_pid'], $this->getClause(), 0);
            $brokenLink['linkTarget'] = $linkType->getBrokenUrl($brokenLink);
            $brokenLink['linkMessage'] = $this->getLinkMessage($brokenLink, $linkType);

            $brokenLink['hash'] = md5($brokenLink['record_uid'] . $brokenLink['record_pid'] . $brokenLink['url']);

            if (!isset($persistentBrokenLinks[$brokenLink['hash']])) {
                $brokenLink['suppressed'] = 0;
                try {
                    $brokenLink['persistentUid'] = $this->createNewPersistentBrokenLink($brokenLink['hash']);
                } catch (\Doctrine\DBAL\Exception $e) {
                    $view->assign('error', true);
                    return $view->render();
                }
                $enabledBrokenLinks[$brokenLink['hash']] = $brokenLink;
                continue;
            }

            $brokenLink['suppressed'] = $persistentBrokenLinks[$brokenLink['hash']]['suppressed'];
            $brokenLink['persistentUid'] = $persistentBrokenLinks[$brokenLink['hash']]['uid'];

            if ($brokenLink['suppressed']) {
                $suppressedBrokenLinks[$brokenLink['hash']] = $brokenLink;
                continue;
            }
            $enabledBrokenLinks[$brokenLink['hash']] = $brokenLink;
        }

        $view->assignMultiple([
            'brokenLinks' => $enabledBrokenLinks,
            'suppressedBrokenLinks' => $suppressedBrokenLinks,
            'configuration' => $this->configuration,
            'dateFormat' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] . ' ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'],
        ]);

        return $view->render('BrokenLinksWidget');
    }

    public function getCssFiles(): array
    {
        return [
            'EXT:editor_widgets/Resources/Public/Css/backend.css',
        ];
    }

    public function getJavaScriptModuleInstructions(): array
    {
        return [
            JavaScriptModuleInstruction::create('@typo3/backend/ajax-data-handler.js'),
        ];
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

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
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
        return (int)BackendUtility::getRecord(
            $tableName,
            $recordUid,
            't3ver_wsid'
        )['t3ver_wsid'];
    }

    protected function getPersistentBrokenLinks(): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::PERSISTENT_TABLE);
        $links = $queryBuilder
            ->select('uid', 'linkvalidator_link', 'suppressed')
            ->from(self::PERSISTENT_TABLE)
            ->executeQuery()
            ->fetchAllAssociative();
        return array_column($links, null, 'linkvalidator_link');
    }

    protected function createNewPersistentBrokenLink(string $hash): int
    {
        $connection = $this->connectionPool->getConnectionForTable(self::PERSISTENT_TABLE);
        $connection->insert(
            self::PERSISTENT_TABLE,
            ['linkvalidator_link' => $hash],
            [Connection::PARAM_STR]
        );
        return $connection->lastInsertId(self::PERSISTENT_TABLE);
    }
}
