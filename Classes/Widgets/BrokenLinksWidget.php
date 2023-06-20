<?php

namespace Sitegeist\WidgetMirror\Widgets;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Linkvalidator\Linktype\AbstractLinktype;

class BrokenLinksWidget implements WidgetInterface
{
    public function __construct(
        private ?WidgetConfigurationInterface $configuration = null,
        private ?StandaloneView $view = null,
        private ?ConnectionPool $connectionPool = null
    )
    {}

    public function renderWidgetContent(): string
    {
        $queryBuilder = $this->connectionPool->getConnectionForTable('tx_linkvalidator_link')->createQueryBuilder();
        $languageServiceFactory = GeneralUtility::makeInstance(LanguageServiceFactory::class);
        $GLOBALS['LANG'] = $languageServiceFactory->createFromUserPreferences($GLOBALS['BE_USER']);
        $GLOBALS['LANG']->includeLLFile('EXT:linkvalidator/Resources/Private/Language/Module/locallang.xlf');

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
        }

        $this->view->setTemplate('Widget/BrokenLinksWidget');
        $this->view->assignMultiple([
            'brokenLinks' => $brokenLinks,
            'configuration' => $this->configuration
        ]);

        return $this->view->render();
    }
}
