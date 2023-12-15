<?php

namespace Sitegeist\EditorWidgets\Widgets;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Dashboard\Widgets\AdditionalCssInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;

class LatestRedirectsWidget implements WidgetInterface, AdditionalCssInterface
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
        $queryBuilder = $this->connectionPool->getConnectionForTable('sys_redirect')->createQueryBuilder();

        $redirects = $queryBuilder
            ->select('uid', 'updatedon', 'source_host', 'source_path', 'target', 'target_statuscode', 'hitcount', 'lasthiton')
            ->from('sys_redirect')
            ->addOrderBy('updatedon', 'desc')
            ->setMaxResults(10)
            ->execute()
            ->fetchAllAssociative();


        $this->view->setTemplate('Widget/LatestRedirectsWidget');
        $this->view->assignMultiple([
            'redirects' => $redirects,
            'configuration' => $this->configuration
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
