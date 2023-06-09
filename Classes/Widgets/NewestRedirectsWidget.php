<?php

namespace Sitegeist\WidgetMirror\Widgets;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;

class NewestRedirectsWidget implements WidgetInterface
{
    /**
     * @var WidgetConfigurationInterface
     */
    private $configuration;

    /**
     * @var StandaloneView
     */
    private $view;

    /**
     * @var ConnectionPool
     */
    private $connectionPool;

    public function __construct(
        WidgetConfigurationInterface $configuration,
        StandaloneView               $view,
        ConnectionPool               $connectionPool
    )
    {
        $this->configuration = $configuration;
        $this->view = $view;
        $this->connectionPool = $connectionPool;
    }

    public function renderWidgetContent(): string
    {
        $queryBuilder = $this->connectionPool->getConnectionForTable('sys_file')->createQueryBuilder();

        $redirects = $queryBuilder
            ->select('uid', 'updatedon', 'source_host', 'source_path', 'target', 'target_statuscode', 'hitcount', 'lasthiton')
            ->from('sys_redirect')
            ->addOrderBy('updatedon', 'desc')
            ->setMaxResults(10)
            ->execute()
            ->fetchAllAssociative();


        $this->view->setTemplate('Widget/NewestRedirectsWidget');
        $this->view->assignMultiple([
            'redirects' => $redirects,
            'configuration' => $this->configuration
        ]);

        return $this->view->render();
    }
}
