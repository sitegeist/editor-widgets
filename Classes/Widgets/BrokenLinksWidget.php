<?php

namespace Sitegeist\WidgetMirror\Widgets;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Linkvalidator\Linktype\AbstractLinktype;

class BrokenLinksWidget implements WidgetInterface
{
    public function __construct(
        private WidgetConfigurationInterface $configuration,
        private StandaloneView $view,
        private ConnectionPool $connectionPool,
        private readonly array $options = []
    )
    {}

    public function renderWidgetContent(): string
    {
        $languageServiceFactory = GeneralUtility::makeInstance(LanguageServiceFactory::class);
        $GLOBALS['LANG'] = $languageServiceFactory->createFromUserPreferences($GLOBALS['BE_USER']);
        $GLOBALS['LANG']->includeLLFile('EXT:linkvalidator/Resources/Private/Language/Module/locallang.xlf');

        $queryBuilder = $this->connectionPool->getConnectionForTable('tx_linkvalidator_link')->createQueryBuilder();
        $brokenLinks = $queryBuilder
            ->select('*')
            ->from('tx_linkvalidator_link')
            ->addOrderBy('last_check', 'desc')
            ->execute()
            ->fetchAllAssociative();

        foreach ($brokenLinks as &$brokenLink) {
            /** @var AbstractLinktype $linkType */
            $linkType = GeneralUtility::makeInstance($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'][$brokenLink['link_type']]);
            $brokenLink['errorMessage'] = $linkType->getErrorMessage(json_decode($brokenLink['url_response'], true)['errorParams']);
            $brokenLink['path'] = BackendUtility::getRecordPath($brokenLink['record_pid'], $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW), 0);
            $brokenLink['linktarget'] = $linkType->getBrokenUrl($brokenLink);

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
        $response = json_decode($brokenLink['url_response'], true);
        if ($response['valid'] ?? false) {
            return '';
        }

        return $linkType->getErrorMessage($response['errorParams'] ?? ['errorType' => 'unknown', 'exception' => 'Invalid response']);
    }
}
