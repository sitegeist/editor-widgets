<?php

namespace Sitegeist\WidgetMirror\Widgets;

use TYPO3\CMS\Core\Database\ConnectionPool;
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
        }

        $this->view->assignMultiple([
            'brokenLinks' => $brokenLinks,
            'configuration' => $this->configuration
        ]);
        $this->view->setTemplate('Widget/BrokenLinksWidget');

        return $this->view->render();
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
