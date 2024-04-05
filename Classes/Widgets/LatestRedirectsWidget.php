<?php

namespace Sitegeist\EditorWidgets\Widgets;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Dashboard\Widgets\AdditionalCssInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;
use TYPO3\CMS\Dashboard\Widgets\RequestAwareWidgetInterface;

class LatestRedirectsWidget implements WidgetInterface, RequestAwareWidgetInterface, AdditionalCssInterface
{
    private ServerRequestInterface $request;

    public function __construct(
        private readonly BackendViewFactory $backendViewFactory,
        private ConnectionPool $connectionPool,
        private WidgetConfigurationInterface $configuration,
        private readonly array $options = []
    )
    {}

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    public function renderWidgetContent(): string
    {
        $queryBuilder = $this->connectionPool->getConnectionForTable('sys_redirect')->createQueryBuilder();

        $redirects = $queryBuilder
            ->select('uid', 'updatedon', 'source_host', 'source_path', 'target', 'target_statuscode', 'hitcount', 'lasthiton')
            ->from('sys_redirect')
            ->addOrderBy('updatedon', 'desc')
            ->setMaxResults(10)
            ->fetchAllAssociative();

        $view = $this->backendViewFactory->create($this->request, ['sitegeist/editor-widgets']);
        $view->assignMultiple([
            'redirects' => $redirects,
            'configuration' => $this->configuration
        ]);

        return $view->render('LatestRedirectsWidget');
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
