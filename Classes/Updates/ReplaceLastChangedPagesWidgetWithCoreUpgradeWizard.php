<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace Sitegeist\EditorWidgets\Updates;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * @since 13.0
 */
#[UpgradeWizard('editorWidgetsReplaceLastChangedPagesWidgetWithCore')]
class ReplaceLastChangedPagesWidgetWithCoreUpgradeWizard implements UpgradeWizardInterface
{
    protected const TABLE_NAME = 'be_dashboards';

    public function getTitle(): string
    {
        return 'Replace editor-widget "Last changed pages" widget with Core Widget';
    }

    public function getDescription(): string
    {
        return 'The widget was integrated into the TYPO3 Core as "Latest changed pages" widget.';
    }

    public function getPrerequisites(): array
    {
        return [];
    }

    public function updateNecessary(): bool
    {
        return $this->hasRecordsToUpdate();
    }

    public function executeUpdate(): bool
    {
        $connection = $this->getConnectionPool()->getConnectionForTable(self::TABLE_NAME);

        foreach ($this->getRecordsToUpdate() as $record) {

            $widgets = json_decode($record['widgets'], true);
            foreach ($widgets as &$widget) {
                if ($widget['identifier'] == 'Sitegeist\EditorWidgets\Widgets\LastChangedPagesWidget') {
                    $widget['identifier'] = 'latestChangedPages';
                }
            }

            $connection->update(
                self::TABLE_NAME,
                ['widgets' => json_encode($widgets)],
                ['identifier' => $record['identifier']]
            );
        }

        return true;
    }

    protected function hasRecordsToUpdate(): bool
    {
        return (bool)$this->getPreparedQueryBuilder()->count('uid')->executeQuery()->fetchOne();
    }

    protected function getRecordsToUpdate(): array
    {
        return $this->getPreparedQueryBuilder()->select('identifier', 'widgets')->executeQuery()->fetchAllAssociative();
    }

    protected function getPreparedQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(self::TABLE_NAME);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->like('widgets', $queryBuilder->createNamedParameter('%LastChangedPagesWidget%', Connection::PARAM_STR))
            );

        return $queryBuilder;
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
