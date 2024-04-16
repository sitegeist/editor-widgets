<?php

namespace Sitegeist\EditorWidgets\Widgets;

use Sitegeist\EditorWidgets\Traits\RequestAwareTrait;
use Sitegeist\EditorWidgets\Traits\WidgetTrait;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Dashboard\Widgets\RequestAwareWidgetInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;

final class IndexedSearchStatisticWidget implements WidgetInterface, RequestAwareWidgetInterface
{
    use RequestAwareTrait;
    use WidgetTrait;

    const NUM_ROWS = 30;

    public function __construct(
        private readonly BackendViewFactory $backendViewFactory,
        private readonly ConnectionPool $connectionPool,
        private readonly Context $context,
        private readonly WidgetConfigurationInterface $configuration,
        private readonly array $options = []
    ) {
    }

    public function renderWidgetContent(): string
    {
        $expressionBuilder = $this->connectionPool->getQueryBuilderForTable('index_stat_word')->expr();

        $currentTimestamp = $this->context->getPropertyFromAspect('date', 'timestamp');

        $last24hours = $expressionBuilder->gt('tstamp', $currentTimestamp - 86400);
        $last30days = $expressionBuilder->gt('tstamp', $currentTimestamp - 30 * 86400);

        $view = $this->backendViewFactory->create($this->request, ['sitegeist/editor-widgets']);
        $view->assignMultiple([
            'last24hours' => $this->getSearchWords($last24hours),
            'last30days' => $this->getSearchWords($last30days),
            'all' => $this->getSearchWords(),
            'configuration' => $this->configuration,
        ]);

        return $view->render('IndexedSearchStatisticWidget');
    }

    private function getSearchWords(string $additionalWhere = ''): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('index_stat_word');
        $queryBuilder
            ->select('word')
            ->addSelectLiteral($queryBuilder->expr()->count('*', 'c'))
            ->from('index_stat_word')
            ->groupBy('word')
            ->orderBy('c', 'desc')
            ->setMaxResults(self::NUM_ROWS);

        if (!empty($additionalWhere)) {
            $queryBuilder->andWhere(QueryHelper::stripLogicalOperatorPrefix($additionalWhere));
        }

        return $queryBuilder->executeQuery()->fetchAllAssociative();
    }
}
