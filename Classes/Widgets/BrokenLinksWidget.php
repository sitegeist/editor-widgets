<?php

namespace Sitegeist\EditorWidgets\Widgets;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Dashboard\Widgets\AdditionalCssInterface;
use TYPO3\CMS\Dashboard\Widgets\RequireJsModuleInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Linkvalidator\Linktype\AbstractLinktype;
use TYPO3\CMS\Linkvalidator\Repository\BrokenLinkRepository;
use TYPO3\CMS\Linkvalidator\Repository\PagesRepository;

class BrokenLinksWidget implements WidgetInterface, AdditionalCssInterface, RequireJsModuleInterface
{
    const PAGE_ID = 0;

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
        $hiddenBrokenLinks = [];

        foreach ($brokenLinks as $key => &$brokenLink ) {
            /** @var AbstractLinktype $linkType */
            $linkType = GeneralUtility::makeInstance($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'][$brokenLink['link_type']]);
            $brokenLink['path'] = BackendUtility::getRecordPath($brokenLink['record_pid'], $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW), 0);
            $brokenLink['linkTarget'] = $linkType->getBrokenUrl($brokenLink);
            $brokenLink['linkMessage'] = $this->getLinkMessage($brokenLink, $linkType);

            if ($brokenLink['tx_editor_widgets_hidden']) {
                $hiddenBrokenLinks[] = $brokenLink;
                unset($brokenLinks[$key]);
            }
        }

        $this->view->assignMultiple([
            'brokenLinks' => $brokenLinks,
            'hiddenBrokenLinks' => $hiddenBrokenLinks,
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

    public function getCssFiles(): array
    {
       return [
           'EXT:editor_widgets/Resources/Public/Css/backend.css',
       ];
    }

    public function getRequireJsModules(): array
    {
        return [
            'TYPO3/CMS/Backend/AjaxDataHandler',
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
        $permsClause = (string)$this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW);
        $pageList = $this->pagesRepository->getAllSubpagesForPage(
            self::PAGE_ID,
            0,
            $permsClause,
            $checkForHiddenPages
        );
        // Always add the current page, because we are just displaying the results
        $pageList[] = self::PAGE_ID;
        $pageTranslations = $this->pagesRepository->getTranslationForPage(
            self::PAGE_ID,
            $permsClause,
            $checkForHiddenPages
        );
        return array_merge($pageList, $pageTranslations);
    }

    protected function getSearchFields(): array
    {
        return BackendUtility::getPagesTSconfig(self::PAGE_ID)['mod.']['linkvalidator.']['searchFields.'] ?? [];
    }
}
